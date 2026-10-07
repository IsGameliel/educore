<?php

use App\Models\{AcademicSession, CourseRegistration, Courses, Department, Faculty, User};
use App\Services\Academic\ResultRegistration;

it('lists identical courses once and finds registrations linked to either copy', function () {
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Food Science', 'faculty_id' => $faculty->id]);
    $session = AcademicSession::create(['name' => '2021/2022', 'start_year' => 2021, 'end_year' => 2022, 'is_active' => true]);
    $attributes = ['code' => 'PHY 101', 'title' => 'Physics', 'credit_unit' => 3, 'semester' => 'First', 'department_id' => $department->id, 'academic_session_id' => $session->id, 'level' => '100'];
    $first = Courses::create($attributes);
    $duplicate = Courses::create($attributes);
    $secondSemester = Courses::create(array_replace($attributes, ['semester' => 'Second']));
    $student = User::factory()->create(['usertype' => 'student', 'department_id' => $department->id, 'level' => '100']);
    $registration = CourseRegistration::create(['user_id' => $student->id, 'course_id' => $duplicate->id, 'semester' => 'First', 'session' => $session->name, 'status' => 'registered', 'registration_date' => now()]);
    expect(Courses::uniqueOptions(Courses::all())->pluck('id')->all())->toBe([$first->id, $secondSemester->id])
        ->and(ResultRegistration::requireForCourse($student->id, $first, $session->name, 'First')->id)->toBe($registration->id);
    $template = new \App\Exports\ResultUploadTemplateSheet($first->load('academicSession'), 'PHY 101');
    expect($template->array()[8][1])->toBe($student->matric_number);
    $admin = User::factory()->create(['usertype' => 'admin']);
    $this->actingAs($admin)->get(route('admin.results.upload'))->assertOk()->assertViewHas('courses', fn ($courses) => $courses->where('semester', 'First')->count() === 1);
    $this->actingAs($student);
    $controller = new \App\Http\Controllers\CourseRegistrationController();
    $request = \Illuminate\Http\Request::create('/', 'GET', ['level' => '100', 'semester' => 'First', 'session' => $session->name]);
    expect($controller->getCoursesByLevel($request)->getData())->toHaveCount(1);
});
