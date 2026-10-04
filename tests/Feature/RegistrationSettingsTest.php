<?php

use App\Models\{AcademicSession, CourseRegistration, Courses, Department, Faculty, RegistrationSetting, TuitionAdjustment, TuitionSchedule, User};
use App\Services\TuitionBilling;

function registrationSettingsFixture(bool $withFees = false): array
{
    $session = AcademicSession::create(['name' => '2026/2027', 'start_year' => 2026, 'end_year' => 2027, 'is_active' => true]);
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $student = User::factory()->create(['usertype' => 'student', 'department_id' => $department->id, 'level' => '100', 'entry_year' => 2026]);
    $course = Courses::create(['code' => 'CSC101', 'title' => 'Computing', 'credit_unit' => 3, 'semester' => 'First', 'department_id' => $department->id, 'level' => '100', 'academic_session_id' => $session->id]);
    $invoice = null;
    if ($withFees) {
        $schedule = TuitionSchedule::create([
            'academic_session_id' => $session->id, 'department_id' => $department->id,
            'level' => '100', 'category' => 'new', 'period' => 'Annual', 'first_percent' => 100,
            'items' => [['label' => 'Tuition', 'amount' => 100000]], 'amount' => 100000,
            'due_date' => '2026-11-01', 'status' => 'published', 'created_by' => User::factory()->create(['usertype' => 'admin'])->id,
        ]);
        $invoice = app(TuitionBilling::class)->issue($student, $schedule);
    }
    return [$student, $course, $session, $invoice];
}

it('lets only admins view and update registration settings', function () {
    $admin = User::factory()->create(['usertype' => 'admin']);
    $this->actingAs($admin)->get(route('admin.registration-settings.edit'))->assertOk()
        ->assertSee('Course registration is open')->assertSee('Pending fees');
    $this->put(route('admin.registration-settings.update'), ['registration_open' => 0, 'require_fee_clearance' => 0])
        ->assertRedirect(route('admin.registration-settings.edit'))->assertSessionHasNoErrors();
    expect(RegistrationSetting::current()->registration_open)->toBeFalse()
        ->and(RegistrationSetting::current()->require_fee_clearance)->toBeFalse()
        ->and(RegistrationSetting::current()->updated_by)->toBe($admin->id);
    $this->actingAs(User::factory()->create(['usertype' => 'student']))
        ->get(route('admin.registration-settings.edit'))->assertForbidden();
    $this->put(route('admin.registration-settings.update'), ['registration_open' => 1, 'require_fee_clearance' => 1])->assertForbidden();
});

it('rejects invalid settings without changing saved controls', function () {
    $this->actingAs(User::factory()->create(['usertype' => 'admin']))
        ->put(route('admin.registration-settings.update'), ['registration_open' => 'invalid', 'require_fee_clearance' => 2])
        ->assertSessionHasErrors(['registration_open', 'require_fee_clearance']);
    expect(RegistrationSetting::current()->registration_open)->toBeTrue();
});

it('blocks direct student submissions while registration is closed regardless of the fee toggle', function (bool $fees) {
    [$student, $course] = registrationSettingsFixture();
    RegistrationSetting::current()->update(['registration_open' => false, 'require_fee_clearance' => $fees]);
    $this->actingAs($student)->get(route('student.courses.registration'))->assertOk()->assertSee('Course registration is currently closed');
    $this->post(route('student.courses.register'), ['semester' => 'First', 'course_ids' => [$course->id]])
        ->assertSessionHasErrors('course_registration');
    $this->postJson(route('student.courses.register'), ['semester' => 'First', 'course_ids' => [$course->id]])
        ->assertUnprocessable()->assertJsonValidationErrors('course_registration');
    expect(CourseRegistration::count())->toBe(0);
    RegistrationSetting::current()->update(['registration_open' => true]);
    $this->post(route('student.courses.register'), ['semester' => 'First', 'course_ids' => [$course->id]])->assertSessionHasNoErrors();
    expect(CourseRegistration::count())->toBe(1);
})->with([true, false]);

it('blocks unpaid fees when enabled and permits them when disabled', function () {
    [$student, $course, $session, $invoice] = registrationSettingsFixture(true);
    $session->update(['tuition_enabled' => true]);
    $this->actingAs($student)->get(route('student.courses.registration'))->assertOk();
    $this->post(route('student.courses.register'), ['semester' => 'First', 'course_ids' => [$course->id]])->assertSessionHasErrors('tuition');
    expect(CourseRegistration::count())->toBe(0);
    RegistrationSetting::current()->update(['require_fee_clearance' => false]);
    $this->get(route('student.courses.registration'))->assertOk()->assertSee('Fee clearance is currently not required');
    $this->post(route('student.courses.register'), ['semester' => 'First', 'course_ids' => [$course->id]])->assertSessionHasNoErrors();
    expect(CourseRegistration::count())->toBe(1)->and($invoice->fresh()->totals()['balance'])->toBe(100000);
});

it('enforces issued fees even when the session tuition switch is disabled', function () {
    [$student, $course] = registrationSettingsFixture(true);
    $this->actingAs($student)->post(route('student.courses.register'), ['semester' => 'First', 'course_ids' => [$course->id]])
        ->assertSessionHasErrors('tuition');
    expect(CourseRegistration::count())->toBe(0);
});

it('allows cleared students to register when the fee toggle is enabled', function () {
    [$student, $course, $session, $invoice] = registrationSettingsFixture(true);
    TuitionAdjustment::create(['tuition_invoice_id' => $invoice->id, 'type' => 'waiver', 'amount' => 100000, 'reason' => 'Approved tuition waiver', 'recorded_by' => User::factory()->create(['usertype' => 'admin'])->id]);
    $this->actingAs($student)->post(route('student.courses.register'), ['semester' => 'First', 'course_ids' => [$course->id]])
        ->assertSessionHasNoErrors();
    expect(CourseRegistration::count())->toBe(1);
});

it('keeps course validation in force when fee checks are disabled', function () {
    [$student, $course] = registrationSettingsFixture(true);
    RegistrationSetting::current()->update(['require_fee_clearance' => false]);
    $this->actingAs($student)->post(route('student.courses.register'), ['semester' => 'Second', 'course_ids' => [$course->id]])
        ->assertSessionHasErrors('course_registration');
    expect(CourseRegistration::count())->toBe(0);
});
