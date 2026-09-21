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

uses(TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    \App\Models\AcademicSession::create(['name' => '2025/2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
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
        'academic_session_id' => \App\Models\AcademicSession::first()->id,
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

    $registeredCourse = $course;
    if ($course->department_id !== $student->department_id) {
        $registeredCourse = Courses::create(array_merge($course->only(['code', 'title', 'credit_unit', 'semester', 'level', 'academic_session_id']), ['department_id' => $student->department_id]));
    }
    \App\Models\CourseRegistration::create(['user_id' => $student->id, 'course_id' => $registeredCourse->id, 'semester' => 'First', 'session' => '2025/2026', 'status' => 'registered', 'registration_date' => now()]);

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
        'academic_session_id' => \App\Models\AcademicSession::first()->id,
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

    $registeredCourse = $course;
    if ($course->department_id !== $student->department_id) {
        $registeredCourse = Courses::create(array_merge($course->only(['code', 'title', 'credit_unit', 'semester', 'level', 'academic_session_id']), ['department_id' => $student->department_id]));
    }
    \App\Models\CourseRegistration::create(['user_id' => $student->id, 'course_id' => $registeredCourse->id, 'semester' => 'First', 'session' => '2025/2026', 'status' => 'registered', 'registration_date' => now()]);

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
        'academic_session_id' => \App\Models\AcademicSession::first()->id,
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

    $registeredCourse = $course;
    if ($course->department_id !== $student->department_id) {
        $registeredCourse = Courses::create(array_merge($course->only(['code', 'title', 'credit_unit', 'semester', 'level', 'academic_session_id']), ['department_id' => $student->department_id]));
    }
    \App\Models\CourseRegistration::create(['user_id' => $student->id, 'course_id' => $registeredCourse->id, 'semester' => 'First', 'session' => '2025/2026', 'status' => 'registered', 'registration_date' => now()]);

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
        'academic_session_id' => \App\Models\AcademicSession::first()->id,
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

    $registeredCourse = $course;
    if ($course->department_id !== $student->department_id) {
        $registeredCourse = Courses::create(array_merge($course->only(['code', 'title', 'credit_unit', 'semester', 'level', 'academic_session_id']), ['department_id' => $student->department_id]));
    }
    \App\Models\CourseRegistration::create(['user_id' => $student->id, 'course_id' => $registeredCourse->id, 'semester' => 'First', 'session' => '2025/2026', 'status' => 'registered', 'registration_date' => now()]);

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
        'academic_session_id' => \App\Models\AcademicSession::first()->id,
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
        ->and($rows)->toHaveCount(8);
});
