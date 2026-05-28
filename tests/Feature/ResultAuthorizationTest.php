<?php

use App\Models\AcademicSession;
use App\Models\Courses;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Result;
use App\Models\User;

function createSessionScopedResultFixture(): array
{
    $oldSession = AcademicSession::create([
        'name' => '2024/2025',
        'start_year' => 2024,
        'end_year' => 2025,
        'is_active' => false,
    ]);

    $currentSession = AcademicSession::create([
        'name' => '2025/2026',
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
    ]);

    $faculty = Faculty::create([
        'name' => 'Faculty of Science',
        'code' => 'FOS',
    ]);

    $department = Department::create([
        'name' => 'Computer Science',
        'faculty_id' => $faculty->id,
    ]);

    $lecturer = User::factory()->create([
        'usertype' => 'lecturer',
        'department_id' => $department->id,
    ]);

    $student = User::factory()->create([
        'usertype' => 'student',
        'department_id' => $department->id,
        'level' => '100',
        'matric_number' => 'MUI/SBMS/NS/24/0001',
    ]);

    $oldSessionCourse = Courses::create([
        'code' => 'CSC101',
        'title' => 'Introduction to Computing',
        'credit_unit' => 3,
        'semester' => 'First',
        'department_id' => $department->id,
        'level' => '100',
        'academic_session_id' => $oldSession->id,
    ]);

    $currentSessionCourse = Courses::create([
        'code' => 'CSC101',
        'title' => 'Introduction to Computing',
        'credit_unit' => 3,
        'semester' => 'First',
        'department_id' => $department->id,
        'level' => '100',
        'academic_session_id' => $currentSession->id,
    ]);

    $lecturer->assignedCourses()->attach($currentSessionCourse->id);

    $oldResult = Result::create([
        'user_id' => $student->id,
        'uploaded_by' => $lecturer->id,
        'matric_number' => $student->matric_number,
        'session' => '2024/2025',
        'semester' => 'First',
        'level' => '100',
        'course_code' => 'CSC101',
        'course_title' => 'Introduction to Computing',
        'credit_unit' => 3,
        'score' => 65,
        'grade' => 'B',
        'grade_point' => 4,
        'department_id' => $department->id,
    ]);

    $currentResult = Result::create([
        'user_id' => $student->id,
        'uploaded_by' => $lecturer->id,
        'matric_number' => $student->matric_number,
        'session' => '2025/2026',
        'semester' => 'First',
        'level' => '100',
        'course_code' => 'CSC101',
        'course_title' => 'Introduction to Computing',
        'credit_unit' => 3,
        'score' => 70,
        'grade' => 'A',
        'grade_point' => 5,
        'department_id' => $department->id,
    ]);

    return compact('lecturer', 'oldResult', 'currentResult', 'oldSessionCourse', 'currentSessionCourse');
}

it('shows lecturers only results for the academic session of their assigned course', function () {
    ['lecturer' => $lecturer] = createSessionScopedResultFixture();

    $response = $this->actingAs($lecturer)
        ->get(route('lecturer.results.index'))
        ->assertOk()
        ->assertSee('2025/2026');

    expect($response->viewData('results')->pluck('session')->all())->toBe(['2025/2026']);
});

it('prevents lecturers from editing same-code results from an unassigned academic session', function () {
    [
        'lecturer' => $lecturer,
        'oldResult' => $oldResult,
        'currentResult' => $currentResult,
    ] = createSessionScopedResultFixture();

    $this->actingAs($lecturer)
        ->get(route('lecturer.results.edit', $oldResult))
        ->assertForbidden();

    $this->actingAs($lecturer)
        ->get(route('lecturer.results.edit', $currentResult))
        ->assertOk();
});
