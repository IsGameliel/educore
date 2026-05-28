<?php

use App\Models\AcademicSession;
use App\Models\Courses;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use App\Models\CourseRegistration;

it('lets admins choose the active academic session used for student course registration', function () {
    $firstSession = AcademicSession::create([
        'name' => '2024/2025',
        'start_year' => 2024,
        'end_year' => 2025,
        'is_active' => true,
    ]);

    $secondSession = AcademicSession::create([
        'name' => '2025/2026',
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
    ]);

    $faculty = Faculty::create([
        'name' => 'Faculty of Science',
        'code' => 'FOS',
    ]);

    $department = Department::create([
        'name' => 'Computer Science',
        'faculty_id' => $faculty->id,
    ]);

    $admin = User::factory()->create([
        'usertype' => 'admin',
    ]);

    $student = User::factory()->create([
        'usertype' => 'student',
        'department_id' => $department->id,
        'level' => '100',
    ]);

    $course = Courses::create([
        'code' => 'CSC101',
        'title' => 'Introduction to Computing',
        'credit_unit' => 3,
        'semester' => 'First',
        'department_id' => $department->id,
        'level' => '100',
        'academic_session_id' => $secondSession->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.academic-sessions.activate', $secondSession))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success', 'Active academic session set to 2025/2026.');

    expect($firstSession->fresh()?->is_active)->toBeFalse()
        ->and($secondSession->fresh()?->is_active)->toBeTrue()
        ->and($student->fresh()?->level)->toBe('200');

    $this->actingAs($student)
        ->post(route('student.courses.register'), [
            'semester' => 'First',
            'level' => '100',
            'course_ids' => [$course->id],
        ])
        ->assertRedirect(route('student.courses.registered', ['semester' => 'First', 'session' => '2025/2026']));

    expect(CourseRegistration::where('user_id', $student->id)->first()?->session)->toBe('2025/2026');
});

it('promotes students when the active academic session is updated to a later year and caps at 500 level', function () {
    $activeSession = AcademicSession::create([
        'name' => '2024/2025',
        'start_year' => 2024,
        'end_year' => 2025,
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'usertype' => 'admin',
    ]);

    $studentAt100 = User::factory()->create([
        'usertype' => 'student',
        'level' => '100',
    ]);

    $studentAt500 = User::factory()->create([
        'usertype' => 'student',
        'level' => '500',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.academic-sessions.update', $activeSession), [
            'name' => '2025/2026',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success', 'Academic session updated successfully.');

    expect($activeSession->fresh()?->name)->toBe('2025/2026')
        ->and($studentAt100->fresh()?->level)->toBe('200')
        ->and($studentAt500->fresh()?->level)->toBe('500');
});
