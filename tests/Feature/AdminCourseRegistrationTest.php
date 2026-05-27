<?php

use App\Models\AcademicSession;
use App\Models\CourseRegistration;
use App\Models\Courses;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;

it('updates registrations only within the admin selected session', function () {
    AcademicSession::create([
        'name' => '2024/2025',
        'start_year' => 2024,
        'end_year' => 2025,
    ]);

    AcademicSession::create([
        'name' => '2025/2026',
        'start_year' => 2025,
        'end_year' => 2026,
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
    ]);

    CourseRegistration::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'semester' => 'First',
        'session' => '2024/2025',
        'status' => 'registered',
        'registration_date' => now(),
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.course-registrations.edit', $student->id) . '?semester=First&session=2025/2026')
        ->put(route('admin.course-registrations.update', $student->id), [
            'semester' => 'First',
            'session' => '2025/2026',
            'course_ids' => [$course->id],
            'statuses' => [
                $course->id => 'approved',
            ],
        ]);

    $response
        ->assertRedirect(route('admin.course-registrations.show', ['student' => $student->id, 'semester' => 'First', 'session' => '2025/2026']))
        ->assertSessionHas('success', 'Registration updated for ' . $student->name . ' (First Semester, 2025/2026).');

    expect(CourseRegistration::where('user_id', $student->id)->where('session', '2024/2025')->count())->toBe(1)
        ->and(CourseRegistration::where('user_id', $student->id)->where('session', '2025/2026')->count())->toBe(1)
        ->and(CourseRegistration::where('user_id', $student->id)->where('session', '2025/2026')->first()?->status)->toBe('approved');
});
