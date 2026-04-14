<?php

use App\Models\Courses;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;

it('shows a friendly validation error when a student exceeds the course unit limit', function () {
    $faculty = Faculty::create([
        'name' => 'Faculty of Science',
        'code' => 'FOS',
    ]);

    $department = Department::create([
        'name' => 'Computer Science',
        'faculty_id' => $faculty->id,
    ]);

    $student = User::factory()->create([
        'usertype' => 'student',
        'department_id' => $department->id,
        'level' => '100',
    ]);

    $courseA = Courses::create([
        'code' => 'CSC101',
        'title' => 'Introduction to Computing',
        'credit_unit' => 12,
        'semester' => 'First',
        'department_id' => $department->id,
        'level' => '100',
    ]);

    $courseB = Courses::create([
        'code' => 'CSC102',
        'title' => 'Programming Fundamentals',
        'credit_unit' => 13,
        'semester' => 'First',
        'department_id' => $department->id,
        'level' => '100',
    ]);

    $response = $this
        ->actingAs($student)
        ->from(route('student.courses.registration'))
        ->post(route('student.courses.register'), [
            'semester' => 'First',
            'level' => '100',
            'course_ids' => [$courseA->id, $courseB->id],
        ]);

    $response
        ->assertRedirect(route('student.courses.registration'))
        ->assertSessionHasErrors([
            'course_registration' => 'Course unit exceeded. You can register a maximum of 24 units for this semester.',
        ]);
});
