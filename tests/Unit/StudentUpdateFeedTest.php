<?php

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Result;
use App\Models\User;
use App\Support\StudentUpdateFeed;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => ':memory:',
        'database.connections.sqlite.foreign_key_constraints' => false,
    ]);

    DB::purge('sqlite');
    DB::reconnect('sqlite');

    Schema::create('faculties', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('description')->nullable();
        $table->string('code')->nullable();
        $table->timestamps();
    });

    Schema::create('departments', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('description')->nullable();
        $table->unsignedBigInteger('faculty_id')->nullable();
        $table->unsignedTinyInteger('pass_mark')->nullable();
        $table->timestamps();
    });

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('usertype')->default('student');
        $table->string('matric_number')->nullable();
        $table->unsignedBigInteger('department_id')->nullable();
        $table->string('level')->nullable();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->unsignedBigInteger('current_team_id')->nullable();
        $table->string('profile_photo_path')->nullable();
        $table->text('two_factor_secret')->nullable();
        $table->text('two_factor_recovery_codes')->nullable();
        $table->timestamps();
    });

    Schema::create('results', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('uploaded_by')->nullable();
        $table->string('matric_number');
        $table->string('session');
        $table->string('semester');
        $table->string('level');
        $table->string('course_code');
        $table->string('course_title');
        $table->unsignedTinyInteger('credit_unit');
        $table->decimal('ca_score', 5, 2)->nullable();
        $table->decimal('exam_score', 5, 2)->nullable();
        $table->decimal('score', 5, 2);
        $table->string('grade')->nullable();
        $table->decimal('grade_point', 3, 2)->nullable();
        $table->unsignedBigInteger('source_result_id')->nullable();
        $table->unsignedBigInteger('department_id');
        $table->string('transcript_path')->nullable();
        $table->string('full_transcript_path')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('activity_logs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('actor_id')->nullable();
        $table->unsignedBigInteger('target_user_id')->nullable();
        $table->unsignedBigInteger('department_id')->nullable();
        $table->string('action');
        $table->string('description');
        $table->string('subject_type')->nullable();
        $table->unsignedBigInteger('subject_id')->nullable();
        $table->text('properties')->nullable();
        $table->timestamps();
    });
});

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

it('includes result updates caused by pass mark changes in the student feed', function () {
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
        'action' => 'result_updated',
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
        ->and($updates->first()['title'])->toBe('Result Updated')
        ->and($updates->first()['course_code'])->toBe('CSC101');
});
