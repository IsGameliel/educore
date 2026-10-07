<?php

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Result;
use App\Models\User;
use App\Support\StudentUpdateFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('includes department pass mark updates in the student feed', function () {
    $faculty = Faculty::create([
        'name' => 'Science',
    ]);

    $department = Department::create([
        'name' => 'Computer Science',
        'faculty_id' => $faculty->id,
        'pass_mark' => 40,
    ]);

    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin-feed@example.com',
        'usertype' => 'admin',
        'department_id' => $department->id,
        'password' => 'password',
    ]);

    $student = User::create([
        'name' => 'Student User',
        'email' => 'student-feed@example.com',
        'usertype' => 'student',
        'matric_number' => 'CSC/001',
        'department_id' => $department->id,
        'level' => '100',
        'password' => 'password',
    ]);

    ActivityLog::create([
        'actor_id' => $admin->id,
        'target_user_id' => $student->id,
        'department_id' => $department->id,
        'action' => 'pass_mark_updated',
        'description' => 'Department pass mark for Computer Science was updated from 40 to 50.',
        'properties' => [
            'old_pass_mark' => 40,
            'new_pass_mark' => 50,
        ],
    ]);

    $updates = StudentUpdateFeed::forUser($student);

    expect($updates)->toHaveCount(1)
        ->and($updates->first()['title'])->toBe('Pass Mark Updated')
        ->and($updates->first()['details'])->toContain('updated from 40 to 50');
});

it('includes published result notifications in the student feed', function () {
    $faculty = Faculty::create([
        'name' => 'Science',
    ]);

    $department = Department::create([
        'name' => 'Computer Science',
        'faculty_id' => $faculty->id,
        'pass_mark' => 50,
    ]);

    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin-result-feed@example.com',
        'usertype' => 'admin',
        'department_id' => $department->id,
        'password' => 'password',
    ]);

    $student = User::create([
        'name' => 'Student User',
        'email' => 'student-result-feed@example.com',
        'usertype' => 'student',
        'matric_number' => 'CSC/002',
        'department_id' => $department->id,
        'level' => '100',
        'password' => 'password',
    ]);

    $result = Result::create([
        'user_id' => $student->id,
        'uploaded_by' => $admin->id,
        'matric_number' => $student->matric_number,
        'session' => '2025/2026',
        'semester' => 'First',
        'level' => '100',
        'course_code' => 'CSC101',
        'course_title' => 'Introduction to Computing',
        'credit_unit' => 3,
        'score' => 45,
        'grade' => 'F',
        'grade_point' => 0,
        'department_id' => $department->id,
    ]);

    ActivityLog::create([
        'actor_id' => $admin->id,
        'target_user_id' => $student->id,
        'department_id' => $department->id,
        'action' => 'result_published',
        'description' => 'Result grade updated for Student User in CSC101 due to pass mark change from 40 to 50',
        'subject_type' => $result->getMorphClass(),
        'subject_id' => $result->id,
        'properties' => [
            'course_code' => 'CSC101',
            'course_title' => 'Introduction to Computing',
            'semester' => 'First',
            'session' => '2025/2026',
            'old_pass_mark' => 40,
            'new_pass_mark' => 50,
            'old_grade' => 'E',
            'new_grade' => 'F',
        ],
    ]);

    $updates = StudentUpdateFeed::forUser($student);

    expect($updates)->toHaveCount(1)
        ->and($updates->first()['title'])->toBe('Result Published')
        ->and($updates->first()['course_code'])->toBe('CSC101');
});
