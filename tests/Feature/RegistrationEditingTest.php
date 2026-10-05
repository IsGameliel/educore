<?php

use App\Models\{AcademicSession, CourseRegistration, Courses, Department, Faculty, User};
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->withoutVite();
    $this->admin = User::factory()->create(['usertype' => 'admin']);
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $this->student = User::factory()->create(['usertype' => 'student', 'department_id' => $department->id, 'level' => '200']);
    $this->session = AcademicSession::create(['name' => '2025/2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
    $this->oldCourse = Courses::create(['code' => 'OLD101', 'title' => 'Legacy course', 'credit_unit' => 3, 'level' => '100', 'semester' => 'First', 'department_id' => $department->id]);
    $this->newCourse = Courses::create(['code' => 'NEW201', 'title' => 'New course', 'credit_unit' => 3, 'level' => '200', 'semester' => 'Second', 'department_id' => $department->id, 'academic_session_id' => $this->session->id]);
    $this->registration = CourseRegistration::create(['user_id' => $this->student->id, 'course_id' => $this->oldCourse->id, 'semester' => 'First', 'session' => null, 'status' => 'pending']);
    $this->editUrl = route('admin.course-registrations.record.edit', [$this->student, $this->registration]);
    $this->updateUrl = route('admin.course-registrations.record.update', [$this->student, $this->registration]);
    $this->payload = [
        'course_id' => $this->newCourse->id, 'semester' => 'Second', 'session' => $this->session->name, 'status' => 'approved',
        'reason' => 'Corrected the course, semester, and academic session.',
        'fingerprint' => hash_hmac('sha256', json_encode($this->registration->fresh()->getAttributes()), config('app.key')),
    ];
});

it('edits course session semester and status in place and audits the previous values', function () {
    $this->actingAs($this->admin)->get($this->editUrl)->assertOk()->assertSee('Save changes')->assertSee('Legacy course');
    $this->put($this->updateUrl, $this->payload)->assertSessionHasNoErrors()->assertRedirect(route('admin.course-registrations.show', ['student' => $this->student->id, 'semester' => 'Second', 'session' => '2025/2026']));
    $this->assertDatabaseHas('course_registrations', ['id' => $this->registration->id, 'user_id' => $this->student->id, 'course_id' => $this->newCourse->id, 'semester' => 'Second', 'session' => '2025/2026', 'status' => 'approved', 'acted_by' => $this->admin->id]);
    $this->assertDatabaseCount('course_registrations', 1);
    $audit = json_decode(DB::table('activity_logs')->where('action', 'registration_corrected')->value('properties'), true);
    expect($audit['before']['session'])->toBeNull();
    expect($audit['after']['course_id'])->toBe($this->newCourse->id);
});

it('rejects duplicate registrations and mismatched offerings', function () {
    CourseRegistration::create(['user_id' => $this->student->id, 'course_id' => $this->newCourse->id, 'semester' => 'Second', 'session' => '2025/2026', 'status' => 'registered']);
    $this->actingAs($this->admin)->put($this->updateUrl, $this->payload)->assertSessionHasErrors('course_id');
    $this->put($this->updateUrl, array_replace($this->payload, ['semester' => 'First']))->assertSessionHasErrors('course_id');
    expect($this->registration->fresh()->session)->toBeNull();
});

it('rejects stale edits and other student registration IDs', function () {
    $this->registration->update(['status' => 'registered']);
    $this->actingAs($this->admin)->put($this->updateUrl, $this->payload)->assertSessionHasErrors('registration');
    $other = User::factory()->create(['usertype' => 'student']);
    $this->get(route('admin.course-registrations.record.edit', [$other, $this->registration]))->assertNotFound();
    $this->put(route('admin.course-registrations.record.update', [$other, $this->registration]), $this->payload)->assertNotFound();
});

it('restricts editing to administrators', function () {
    $this->actingAs($this->student)->get($this->editUrl)->assertForbidden();
    $this->put($this->updateUrl, $this->payload)->assertForbidden();
});

it('preserves linked results and permits eligible status changes only', function () {
    $this->registration->update(['status' => 'registered']);
    $resultId = DB::table('results')->insertGetId([
        'user_id' => $this->student->id, 'course_registration_id' => $this->registration->id, 'department_id' => $this->student->department_id,
        'matric_number' => 'EDIT-TEST', 'session' => '2025/2026', 'semester' => 'First', 'level' => '100',
        'course_code' => 'OLD101', 'course_title' => 'Legacy course', 'credit_unit' => 3, 'score' => 75, 'workflow_status' => 'published',
    ]);
    $this->payload['fingerprint'] = hash_hmac('sha256', json_encode($this->registration->fresh()->getAttributes()), config('app.key'));
    $this->actingAs($this->admin)->get($this->editUrl)->assertOk()->assertSee('protected');
    $this->put($this->updateUrl, $this->payload)->assertSessionHasErrors('course_registration');
    $statusOnly = array_replace($this->payload, ['course_id' => $this->oldCourse->id, 'session' => null, 'semester' => 'First', 'status' => 'withdrawn']);
    $this->put($this->updateUrl, $statusOnly)->assertSessionHasErrors('course_registration');
    $this->put($this->updateUrl, array_replace($statusOnly, ['status' => 'completed']))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('results', ['id' => $resultId, 'score' => 75, 'workflow_status' => 'published', 'course_registration_id' => $this->registration->id]);
});

it('validates session and status selections', function () {
    $this->actingAs($this->admin)->put($this->updateUrl, array_replace($this->payload, ['session' => 'all', 'status' => 'arbitrary']))
        ->assertSessionHasErrors(['session', 'status']);
    expect($this->registration->fresh()->course_id)->toBe($this->oldCourse->id);
});

it('enforces the destination credit limit and preserves the registration on failure', function () {
    $course = Courses::create(['code' => 'LOAD200', 'title' => 'Existing load', 'credit_unit' => 24, 'level' => '200', 'semester' => 'Second', 'department_id' => $this->student->department_id, 'academic_session_id' => $this->session->id]);
    CourseRegistration::create(['user_id' => $this->student->id, 'course_id' => $course->id, 'session' => '2025/2026', 'semester' => 'Second', 'status' => 'registered']);
    $this->actingAs($this->admin)->put($this->updateUrl, $this->payload)->assertSessionHasErrors('course_id');
    expect($this->registration->fresh()->session)->toBeNull();
    $this->assertDatabaseMissing('activity_logs', ['action' => 'registration_corrected']);
});

it('preserves registrations when tuition clearance rejects the correction', function () {
    $billing = Mockery::mock(\App\Services\TuitionBilling::class);
    $billing->shouldReceive('assertCleared')->once()->andThrow(\Illuminate\Validation\ValidationException::withMessages(['tuition' => 'Tuition clearance required.']));
    $this->app->instance(\App\Services\TuitionBilling::class, $billing);
    $this->actingAs($this->admin)->put($this->updateUrl, $this->payload)->assertSessionHasErrors('tuition');
    expect($this->registration->fresh()->course_id)->toBe($this->oldCourse->id);
});

it('protects carryover course and session links', function () {
    $previous = DB::table('results')->insertGetId([
        'user_id' => $this->student->id, 'department_id' => $this->student->department_id,
        'matric_number' => 'EDIT-TEST', 'session' => '2024/2025', 'semester' => 'First', 'level' => '100',
        'course_code' => 'OLD101', 'course_title' => 'Legacy course', 'credit_unit' => 3, 'score' => 20, 'workflow_status' => 'published',
    ]);
    $this->registration->update(['previous_result_id' => $previous]);
    $this->payload['fingerprint'] = hash_hmac('sha256', json_encode($this->registration->fresh()->getAttributes()), config('app.key'));
    $this->actingAs($this->admin)->put($this->updateUrl, $this->payload)->assertSessionHasErrors('course_id');
    expect($this->registration->fresh()->previous_result_id)->toBe($previous);
});

function anotherRegistrationInEditGroup($test): CourseRegistration
{
    $old = Courses::create(['code' => 'OTHER101', 'title' => 'Another course', 'credit_unit' => 3, 'level' => '100', 'semester' => 'First', 'department_id' => $test->student->department_id]);
    Courses::create(['code' => 'OTHER101', 'title' => 'Another course offering', 'credit_unit' => 3, 'level' => '100', 'semester' => 'Second', 'department_id' => $test->student->department_id, 'academic_session_id' => $test->session->id]);
    return CourseRegistration::create(['user_id' => $test->student->id, 'course_id' => $old->id, 'semester' => 'First', 'session' => null, 'status' => 'pending']);
}

it('updates session and semester for every course in the original group', function () {
    $this->payload['update_scope'] = 'bulk';
    $other = anotherRegistrationInEditGroup($this);
    $untouched = CourseRegistration::create(['user_id' => $this->student->id, 'course_id' => $other->course_id, 'session' => '2024/2025', 'semester' => 'First', 'status' => 'pending']);
    $response = $this->actingAs($this->admin)->get($this->editUrl)->assertOk()->assertSee('all 2 courses');
    $this->payload['group_fingerprint'] = $response->viewData('groupFingerprint');
    $this->put($this->updateUrl, $this->payload)->assertSessionHasNoErrors()->assertSessionHas('success', 'Updated session/semester for all 2 courses in this registration group.');
    $this->assertDatabaseHas('course_registrations', ['id' => $this->registration->id, 'semester' => 'Second', 'session' => '2025/2026', 'status' => 'approved']);
    $this->assertDatabaseHas('course_registrations', ['id' => $other->id, 'semester' => 'Second', 'session' => '2025/2026', 'status' => 'pending']);
    expect($other->fresh()->course_id)->not->toBe($other->course_id);
    expect($untouched->fresh()->session)->toBe('2024/2025');
    $audit = json_decode(DB::table('activity_logs')->where('action', 'registration_group_corrected')->value('properties'), true);
    expect($audit['before'])->toHaveCount(2);
    expect($audit['after'])->toHaveCount(2);
});

it('requires reviewing the whole group and rejects stale changes to another course', function () {
    $this->payload['update_scope'] = 'bulk';
    $other = anotherRegistrationInEditGroup($this);
    $this->actingAs($this->admin)->put($this->updateUrl, $this->payload)->assertSessionHasErrors('registration');
    $response = $this->get($this->editUrl);
    $this->payload['group_fingerprint'] = $response->viewData('groupFingerprint');
    $other->update(['status' => 'registered']);
    $this->put($this->updateUrl, $this->payload)->assertSessionHasErrors('registration');
    expect($this->registration->fresh()->session)->toBeNull();
    expect($other->fresh()->session)->toBeNull();
});

it('leaves the entire group unchanged when another course has result history', function () {
    $this->payload['update_scope'] = 'bulk';
    $other = anotherRegistrationInEditGroup($this);
    DB::table('results')->insert([
        'user_id' => $this->student->id, 'course_registration_id' => $other->id, 'department_id' => $this->student->department_id,
        'matric_number' => 'GROUP-TEST', 'session' => '2025/2026', 'semester' => 'First', 'level' => '100',
        'course_code' => 'OTHER101', 'course_title' => 'Another course', 'credit_unit' => 3, 'score' => 75, 'workflow_status' => 'published',
    ]);
    $this->payload['group_fingerprint'] = $this->actingAs($this->admin)->get($this->editUrl)->viewData('groupFingerprint');
    $this->put($this->updateUrl, $this->payload)->assertSessionHasErrors('course_registration');
    expect($this->registration->fresh()->session)->toBeNull();
    expect($other->fresh()->session)->toBeNull();
    $this->assertDatabaseMissing('activity_logs', ['action' => 'registration_group_corrected']);
});

it('keeps course and status only edits limited to the selected registration', function () {
    $other = anotherRegistrationInEditGroup($this);
    $statusOnly = array_replace($this->payload, ['course_id' => $this->oldCourse->id, 'session' => null, 'semester' => 'First', 'status' => 'rejected']);
    $this->actingAs($this->admin)->put($this->updateUrl, $statusOnly)->assertSessionHasNoErrors();
    expect($this->registration->fresh()->status)->toBe('rejected');
    expect($other->fresh()->status)->toBe('pending');
});

it('keeps single session and semester updates isolated even when other courses share the original group', function () {
    $other = anotherRegistrationInEditGroup($this);
    $original = $other->fresh()->getAttributes();
    $response = $this->actingAs($this->admin)->get($this->editUrl)->assertOk();
    expect($response->viewData('scope'))->toBe('single');
    $this->put($this->updateUrl, array_replace($this->payload, ['update_scope' => 'single']))->assertSessionHasNoErrors();
    expect((array) $other->fresh()->getAttributes())->toBe($original);
    expect($this->registration->fresh()->session)->toBe('2025/2026');
    expect($this->registration->fresh()->semester)->toBe('Second');
});

it('opens the group action in bulk mode and validates the update scope', function () {
    $this->actingAs($this->admin)->get($this->editUrl.'?scope=bulk')->assertOk()->assertViewHas('scope', 'bulk');
    $this->put($this->updateUrl, array_replace($this->payload, ['update_scope' => 'invalid']))->assertSessionHasErrors('update_scope');
    expect($this->registration->fresh()->session)->toBeNull();
});
