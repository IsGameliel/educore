<?php

use App\Models\{AcademicSession, CourseRegistration, Courses, Department, Faculty, RegistrationSetting, StudentAcademicSession, User};

it('shows and registers courses using each historical session level while keeping the active session and profile unchanged', function () {
    $active = AcademicSession::create(['name' => '2025/2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $student = User::factory()->create(['usertype' => 'student', 'department_id' => $department->id, 'level' => '500']);
    RegistrationSetting::current()->update(['registration_open' => true, 'require_fee_clearance' => false]);
    $this->actingAs($student);
    foreach (['2021/2022' => '100', '2022/2023' => '200'] as $name => $level) {
        $session = AcademicSession::create(['name' => $name, 'start_year' => (int) $name, 'end_year' => (int) $name + 1]);
        StudentAcademicSession::create(['user_id' => $student->id, 'academic_session_id' => $session->id, 'level' => $level]);
        $course = Courses::create(['code' => 'CSC '.$level, 'title' => 'Historical course', 'credit_unit' => 3, 'department_id' => $department->id, 'level' => $level, 'semester' => 'First', 'academic_session_id' => $session->id]);
        $wrong = Courses::create(['code' => 'CSC 500', 'title' => 'Wrong level course', 'credit_unit' => 3, 'department_id' => $department->id, 'level' => '500', 'semester' => 'First', 'academic_session_id' => $session->id]);
        $this->get(route('student.courses.registration', ['session' => $name]))->assertOk()
            ->assertViewHas('currentSession', $name)->assertViewHas('sessionLevel', $level)
            ->assertViewHas('courses', fn ($courses) => $courses->modelKeys() === [$course->id])->assertDontSee('Wrong level course');
        $this->getJson(route('student.courses.byLevel', ['session' => $name, 'semester' => 'First', 'level' => '500']))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $course->id);
        $this->post(route('student.courses.register'), ['session' => $name, 'semester' => 'First', 'course_ids' => [$wrong->id]])->assertSessionHasErrors('course_registration');
        $this->post(route('student.courses.register'), ['session' => $name, 'semester' => 'First', 'level' => '500', 'course_ids' => [$course->id]])
            ->assertSessionHasNoErrors()->assertRedirect(route('student.courses.registered', ['session' => $name, 'semester' => 'First']));
        $this->assertDatabaseHas('course_registrations', ['user_id' => $student->id, 'course_id' => $course->id, 'session' => $name]);
        $this->get(route('student.courses.registered', ['session' => $name, 'semester' => 'First']))->assertOk()->assertViewHas('sessionLevel', $level);
    }
    expect($student->fresh()->level)->toBe('500')->and(AcademicSession::current()->id)->toBe($active->id)->and(CourseRegistration::count())->toBe(2);
});

it('blocks historical registration when the student has no confirmed level for the session', function () {
    $session = AcademicSession::create(['name' => '2021/2022', 'start_year' => 2021, 'end_year' => 2022]);
    $student = User::factory()->create(['usertype' => 'student', 'level' => '500']);
    RegistrationSetting::current()->update(['registration_open' => true, 'require_fee_clearance' => false]);
    $this->actingAs($student)->get(route('student.courses.registration', ['session' => $session->name]))
        ->assertOk()->assertSee('has not been set')->assertViewHas('sessionLevel', null);
    $this->post(route('student.courses.register'), ['session' => $session->name, 'semester' => 'First', 'course_ids' => []])->assertSessionHasErrors('session_level');
    expect(CourseRegistration::count())->toBe(0);
});
