<?php

use App\Exports\ResultUploadTemplateSheet;
use App\Imports\ResultsImport;
use App\Models\Courses;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Result;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
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

    Schema::create('courses', function (Blueprint $table) {
        $table->id();
        $table->string('code');
        $table->string('title');
        $table->unsignedTinyInteger('credit_unit');
        $table->string('semester');
        $table->unsignedBigInteger('department_id')->nullable();
        $table->string('level')->nullable();
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

it('imports a result sheet when the metadata uses session as the label', function () {
    $faculty = Faculty::create([
        'name' => 'Science',
    ]);

    $department = Department::create([
        'name' => 'Computer Science',
        'faculty_id' => $faculty->id,
        'pass_mark' => 40,
    ]);

    $course = Courses::create([
        'code' => 'CSC101',
        'title' => 'Introduction to Computing',
        'credit_unit' => 3,
        'semester' => 'First',
        'department_id' => $department->id,
        'level' => '100',
    ]);

    $actor = User::create([
        'name' => 'Result Admin',
        'email' => 'admin@example.com',
        'usertype' => 'admin',
        'department_id' => $department->id,
        'password' => 'password',
    ]);

    $student = User::create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'usertype' => 'student',
        'matric_number' => 'MUI/SBMS/NS/24/0001',
        'department_id' => $department->id,
        'level' => '100',
        'password' => 'password',
    ]);

    $rows = new Collection([
        ['COURSE', 'CSC101'],
        ['TITLE', 'Introduction to Computing'],
        ['SESSION', '2025/2026'],
        ['LEVEL', '100'],
        ['', '', '', '', '', '', ''],
        ['', '', '', '', '', '', ''],
        ['', '', '', '', '', '', ''],
        ['S/NO', 'MATRIC NO.', 'NAME', 'CA', 'EXAM', 'Total'],
        [1, 'MUI/SBMS/NS/24/0001', '', 24, 46, 70],
    ]);

    $import = new ResultsImport($course, $actor->id);

    $import->collection($rows);

    expect(Result::query()->count())->toBe(1);

    $result = Result::query()->first();

    expect($result)->not->toBeNull()
        ->and($result->user_id)->toBe($student->id)
        ->and($result->session)->toBe('2025/2026')
        ->and((float) $result->ca_score)->toBe(24.0)
        ->and((float) $result->exam_score)->toBe(46.0)
        ->and((float) $result->score)->toBe(70.0)
        ->and($result->grade)->toBe('A');
});

it('imports a result sheet when the session is stored in a single merged cell style value', function () {
    $faculty = Faculty::create([
        'name' => 'Science',
    ]);

    $department = Department::create([
        'name' => 'Computer Science',
        'faculty_id' => $faculty->id,
        'pass_mark' => 40,
    ]);

    $course = Courses::create([
        'code' => 'CSC101',
        'title' => 'Introduction to Computing',
        'credit_unit' => 3,
        'semester' => 'First',
        'department_id' => $department->id,
        'level' => '100',
    ]);

    $actor = User::create([
        'name' => 'Result Admin',
        'email' => 'admin2@example.com',
        'usertype' => 'admin',
        'department_id' => $department->id,
        'password' => 'password',
    ]);

    $student = User::create([
        'name' => 'Grace Hopper',
        'email' => 'grace@example.com',
        'usertype' => 'student',
        'matric_number' => 'MUI/SBMS/NS/24/0002',
        'department_id' => $department->id,
        'level' => '100',
        'password' => 'password',
    ]);

    $rows = new Collection([
        ['COURSE CODE CSC101'],
        ['ACADEMIC SESSION 2025/2026'],
        ['100 LEVEL'],
        ['', '', '', '', '', '', ''],
        ['', '', '', '', '', '', ''],
        ['', '', '', '', '', '', ''],
        ['', '', '', '', '', '', ''],
        ['S/NO', 'MATRIC NO.', 'NAME', 'CA', 'EXAM', 'Total'],
        [1, 'MUI/SBMS/NS/24/0002', 'Grace Hopper', 20, 40, 60],
    ]);

    $import = new ResultsImport($course, $actor->id);

    $import->collection($rows);

    $result = Result::query()->where('user_id', $student->id)->first();

    expect($result)->not->toBeNull()
        ->and($result->session)->toBe('2025/2026')
        ->and((float) $result->score)->toBe(60.0);
});

it('imports a shared course for a student department even when the selected course record belongs to another department', function () {
    $faculty = Faculty::create([
        'name' => 'Engineering and Science',
    ]);

    $biotechnology = Department::create([
        'name' => 'Biotechnology',
        'faculty_id' => $faculty->id,
        'pass_mark' => 45,
    ]);

    $computerEngineering = Department::create([
        'name' => 'Computer Engineering',
        'faculty_id' => $faculty->id,
        'pass_mark' => 40,
    ]);

    $course = Courses::create([
        'code' => 'ENT 101',
        'title' => 'Entrepreneurship Fundamentals',
        'credit_unit' => 2,
        'semester' => 'First',
        'department_id' => $biotechnology->id,
        'level' => '100',
    ]);

    $actor = User::create([
        'name' => 'Result Admin',
        'email' => 'admin3@example.com',
        'usertype' => 'admin',
        'department_id' => $biotechnology->id,
        'password' => 'password',
    ]);

    $student = User::create([
        'name' => 'Alan Turing',
        'email' => 'alan@example.com',
        'usertype' => 'student',
        'matric_number' => 'MUI/SET/22/0012',
        'department_id' => $computerEngineering->id,
        'level' => '100',
        'password' => 'password',
    ]);

    $rows = new Collection([
        ['COURSE CODE', 'ENT 101'],
        ['ACADEMIC SESSION', '2025/2026'],
        ['LEVEL', '100'],
        ['', '', '', '', '', '', ''],
        ['', '', '', '', '', '', ''],
        ['', '', '', '', '', '', ''],
        ['', '', '', '', '', '', ''],
        ['S/NO', 'MATRIC NO.', 'NAME', 'CA', 'EXAM', 'Total'],
        [1, 'MUI/SET/22/0012', 'Alan Turing', 18, 40, 58],
    ]);

    $import = new ResultsImport($course, $actor->id);

    $import->collection($rows);

    $result = Result::query()->where('user_id', $student->id)->first();

    expect($result)->not->toBeNull()
        ->and($result->course_code)->toBe('ENT 101')
        ->and($result->department_id)->toBe($computerEngineering->id)
        ->and($result->grade)->toBe('C');
});

it('imports a result sheet when the table starts from column a on row 8', function () {
    $faculty = Faculty::create([
        'name' => 'Science',
    ]);

    $department = Department::create([
        'name' => 'Computer Science',
        'faculty_id' => $faculty->id,
        'pass_mark' => 40,
    ]);

    $course = Courses::create([
        'code' => 'CSC102',
        'title' => 'Programming Fundamentals',
        'credit_unit' => 3,
        'semester' => 'First',
        'department_id' => $department->id,
        'level' => '100',
    ]);

    $actor = User::create([
        'name' => 'Result Admin',
        'email' => 'admin4@example.com',
        'usertype' => 'admin',
        'department_id' => $department->id,
        'password' => 'password',
    ]);

    $student = User::create([
        'name' => 'Katherine Johnson',
        'email' => 'katherine@example.com',
        'usertype' => 'student',
        'matric_number' => 'MUI/SBMS/NS/24/0003',
        'department_id' => $department->id,
        'level' => '100',
        'password' => 'password',
    ]);

    $rows = new Collection([
        ['COURSE CODE', 'CSC102'],
        ['ACADEMIC SESSION', '2025/2026'],
        ['LEVEL', '100'],
        ['', '', '', '', '', '', ''],
        ['', '', '', '', '', '', ''],
        ['', '', '', '', '', '', ''],
        ['', '', '', '', '', '', ''],
        ['S/NO', 'MATRIC NO.', 'CA', 'EXAM', 'Total'],
        [1, 'MUI/SBMS/NS/24/0003', 25, 42, 67],
    ]);

    $import = new ResultsImport($course, $actor->id);

    $import->importRows($rows);

    $result = Result::query()->where('user_id', $student->id)->first();

    expect($result)->not->toBeNull()
        ->and($result->session)->toBe('2025/2026')
        ->and((float) $result->ca_score)->toBe(25.0)
        ->and((float) $result->exam_score)->toBe(42.0)
        ->and((float) $result->score)->toBe(67.0);
});

it('generates the result upload template with an optional name column', function () {
    $faculty = Faculty::create([
        'name' => 'Science',
    ]);

    $department = Department::create([
        'name' => 'Computer Science',
        'faculty_id' => $faculty->id,
    ]);

    $course = Courses::create([
        'code' => 'CSC103',
        'title' => 'Computer Applications',
        'credit_unit' => 2,
        'semester' => 'Second',
        'department_id' => $department->id,
        'level' => '100',
    ]);

    $sheet = new ResultUploadTemplateSheet($course, 'CSC103');
    $rows = $sheet->array();

    expect($rows[7])->toBe(['S/NO', 'MATRIC NO.', 'NAME', 'CA', 'EXAM', 'Total'])
        ->and($rows[8][2])->toBe('')
        ->and($rows[9][2])->toBe('');
});
