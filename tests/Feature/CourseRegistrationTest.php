<?php

use App\Models\Courses;
use App\Models\AcademicSession;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;

it('shows a friendly validation error when a student exceeds the course unit limit', function () {
    $academicSession = AcademicSession::create([
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
        'academic_session_id' => $academicSession->id,
    ]);

    $courseB = Courses::create([
        'code' => 'CSC102',
        'title' => 'Programming Fundamentals',
        'credit_unit' => 13,
        'semester' => 'First',
        'department_id' => $department->id,
        'level' => '100',
        'academic_session_id' => $academicSession->id,
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

it('rejects courses that do not belong to the selected semester', function () {
    $academicSession = AcademicSession::create([
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

    $student = User::factory()->create([
        'usertype' => 'student',
        'department_id' => $department->id,
        'level' => '100',
    ]);

    $secondSemesterCourse = Courses::create([
        'code' => 'CSC102',
        'title' => 'Programming Fundamentals',
        'credit_unit' => 3,
        'semester' => 'Second',
        'department_id' => $department->id,
        'level' => '100',
        'academic_session_id' => $academicSession->id,
    ]);

    $response = $this
        ->actingAs($student)
        ->from(route('student.courses.registration'))
        ->post(route('student.courses.register'), [
            'semester' => 'First',
            'level' => '100',
            'course_ids' => [$secondSemesterCourse->id],
        ]);

    $response
        ->assertRedirect(route('student.courses.registration'))
        ->assertSessionHasErrors([
            'course_registration' => 'One or more selected courses are not available for your level, selected semester, or academic session.',
        ]);
});

it('rejects courses that do not belong to the active academic session', function () {
    $oldSession = AcademicSession::create([
        'name' => '2024/2025',
        'start_year' => 2024,
        'end_year' => 2025,
        'is_active' => false,
    ]);

    AcademicSession::create([
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

    $student = User::factory()->create([
        'usertype' => 'student',
        'department_id' => $department->id,
        'level' => '100',
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

    $response = $this
        ->actingAs($student)
        ->from(route('student.courses.registration'))
        ->post(route('student.courses.register'), [
            'semester' => 'First',
            'level' => '100',
            'course_ids' => [$oldSessionCourse->id],
        ]);

    $response
        ->assertRedirect(route('student.courses.registration'))
        ->assertSessionHasErrors([
            'course_registration' => 'One or more selected courses are not available for your level, selected semester, or academic session.',
        ]);
});

it('does not count previous session registrations against the current session total', function () {
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

    $student = User::factory()->create([
        'usertype' => 'student',
        'department_id' => $department->id,
        'level' => '100',
    ]);

    $oldSessionCourse = Courses::create([
        'code' => 'CSC101',
        'title' => 'Introduction to Computing',
        'credit_unit' => 20,
        'semester' => 'First',
        'department_id' => $department->id,
        'level' => '100',
        'academic_session_id' => $oldSession->id,
    ]);

    $currentSessionCourse = Courses::create([
        'code' => 'CSC102',
        'title' => 'Programming Fundamentals',
        'credit_unit' => 10,
        'semester' => 'First',
        'department_id' => $department->id,
        'level' => '100',
        'academic_session_id' => $currentSession->id,
    ]);

    CourseRegistration::create([
        'user_id' => $student->id,
        'course_id' => $oldSessionCourse->id,
        'semester' => 'First',
        'session' => '2024/2025',
        'status' => 'registered',
        'registration_date' => now(),
    ]);

    $response = $this
        ->actingAs($student)
        ->from(route('student.courses.registration'))
        ->post(route('student.courses.register'), [
            'semester' => 'First',
            'level' => '100',
            'course_ids' => [$currentSessionCourse->id],
        ]);

    $response
        ->assertRedirect(route('student.courses.registered', ['semester' => 'First', 'session' => '2025/2026']))
        ->assertSessionHas('success', 'Courses successfully registered!');

    expect(CourseRegistration::where('user_id', $student->id)->where('session', '2024/2025')->count())->toBe(1)
        ->and(CourseRegistration::where('user_id', $student->id)->where('session', '2025/2026')->count())->toBe(1);
});

it('shows a student only the registered courses for the selected session and semester', function () {
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

    $firstSemesterCourse = Courses::create([
        'code' => 'CSC101',
        'title' => 'Introduction to Computing',
        'credit_unit' => 3,
        'semester' => 'First',
        'department_id' => $department->id,
        'level' => '100',
    ]);

    $secondSemesterCourse = Courses::create([
        'code' => 'CSC102',
        'title' => 'Programming Fundamentals',
        'credit_unit' => 3,
        'semester' => 'Second',
        'department_id' => $department->id,
        'level' => '100',
    ]);

    CourseRegistration::create([
        'user_id' => $student->id,
        'course_id' => $firstSemesterCourse->id,
        'semester' => 'First',
        'session' => '2024/2025',
        'status' => 'registered',
        'registration_date' => now(),
    ]);

    CourseRegistration::create([
        'user_id' => $student->id,
        'course_id' => $secondSemesterCourse->id,
        'semester' => 'Second',
        'session' => '2025/2026',
        'status' => 'registered',
        'registration_date' => now(),
    ]);

    $response = $this
        ->actingAs($student)
        ->get(route('student.courses.registered', ['semester' => 'First', 'session' => '2024/2025']));

    $response
        ->assertOk()
        ->assertSee('2024/2025')
        ->assertSee('First')
        ->assertSee('CSC101')
        ->assertDontSee('CSC102');
});
