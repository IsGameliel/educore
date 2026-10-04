<?php

use App\Mail\ClassScheduledNotification;
use App\Models\ActivityLog;
use App\Models\AcademicSession;
use App\Models\ClassSchedule;
use App\Models\Courses;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use App\Support\StudentUpdateFeed;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $this->department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $this->admin = User::factory()->create(['usertype' => 'admin', 'department_id' => $this->department->id]);
    $this->lecturer = User::factory()->create(['usertype' => 'lecturer', 'department_id' => $this->department->id]);
    $session = AcademicSession::create(['name' => '2026/2027', 'start_year' => 2026, 'end_year' => 2027, 'is_active' => true]);
    $this->course = Courses::create([
        'code' => 'CSC101', 'title' => 'Introduction to Computing', 'credit_unit' => 3,
        'semester' => 'First', 'level' => '100', 'department_id' => $this->department->id,
        'academic_session_id' => $session->id,
    ]);
    $this->payload = [
        'department_id' => $this->department->id, 'level' => '100', 'semester' => 'First',
        'subject' => $this->course->id, 'lecturer_id' => $this->lecturer->id,
        'day' => 'Monday', 'start_time' => '09:00', 'end_time' => '10:00', 'room' => 'Science Hall',
    ];
});

it('queues personalized class emails and dashboard notifications for every student in the department', function () {
    $students = collect(['100', '200'])->map(fn ($level) => User::factory()->create([
        'usertype' => 'student', 'department_id' => $this->department->id, 'level' => $level,
    ]));
    $otherDepartment = Department::create(['name' => 'Physics', 'faculty_id' => $this->department->faculty_id]);
    $otherStudent = User::factory()->create(['usertype' => 'student', 'department_id' => $otherDepartment->id]);
    User::factory()->create(['usertype' => 'applicant', 'department_id' => $this->department->id]);

    $this->actingAs($this->admin)->post(route('admin.class-schedules.store'), $this->payload)
        ->assertRedirect(route('admin.class-schedules.index'))->assertSessionHasNoErrors();

    $this->assertDatabaseCount('class_schedules', 1);
    expect(ActivityLog::where('action', 'class_scheduled')->count())->toBe(1);
    Mail::assertQueuedCount(2);
    foreach ($students as $student) {
        Mail::assertQueued(ClassScheduledNotification::class, fn ($mail) => $mail->hasTo($student->email)
            && $mail->studentName === $student->name && $mail->afterCommit === true);
        $updates = StudentUpdateFeed::forUser($student);
        expect($updates)->toHaveCount(1)
            ->and($updates->first()['title'])->toBe('Class Scheduled')
            ->and($updates->first()['details'])->toContain('CSC101', 'Monday', '09:00', '10:00', 'Science Hall', $this->lecturer->name)
            ->and($updates->first()['semester'])->toBe('First');
        $this->actingAs($student)->get(route('dashboard'))->assertOk()->assertSee('Class Scheduled')->assertSee('Science Hall');
    }
    expect(StudentUpdateFeed::forUser($otherStudent))->toBeEmpty();
    $this->actingAs($otherStudent)->get(route('dashboard'))->assertOk()->assertDontSee('Class Scheduled');

    $mail = new ClassScheduledNotification(ClassSchedule::first(), $this->course->title, $students->first()->name);
    $html = $mail->render();
    expect($html)->toContain(e($students->first()->name), 'Introduction to Computing', 'Computing', 'Monday', 'Science Hall', e($this->lecturer->name));
});

it('does not notify students when schedule validation fails', function () {
    User::factory()->create(['usertype' => 'student', 'department_id' => $this->department->id]);
    $this->actingAs($this->admin)->post(route('admin.class-schedules.store'), array_replace($this->payload, ['end_time' => '08:00']))
        ->assertSessionHasErrors('end_time');
    $this->assertDatabaseCount('class_schedules', 0);
    expect(ActivityLog::where('action', 'class_scheduled')->count())->toBe(0);
    Mail::assertNothingQueued();
});

it('rejects unauthorized scheduling without notifying anyone', function () {
    $this->actingAs($this->lecturer)->post(route('admin.class-schedules.store'), $this->payload)->assertForbidden();
    $this->assertDatabaseCount('class_schedules', 0);
    Mail::assertNothingQueued();
});

it('records the department notification even when no students are enrolled yet', function () {
    $this->actingAs($this->admin)->post(route('admin.class-schedules.store'), $this->payload)->assertSessionHasNoErrors();
    $this->assertDatabaseCount('class_schedules', 1);
    expect(ActivityLog::where('action', 'class_scheduled')->count())->toBe(1);
    Mail::assertNothingQueued();
});
