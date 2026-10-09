<?php

use App\Models\{AcademicSession, CourseRegistration, Courses, Department, Faculty, LateRegistrationCharge, Payment, RegistrationSetting, User};
use App\Services\Academic\LateRegistrationFee;
use App\Services\PaystackPayments;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.paystack.secret_key' => 'sk_test_late_registration']);
    Http::preventStrayRequests();
    $this->session = AcademicSession::create(['name' => '2025/2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $this->student = User::factory()->create(['usertype' => 'student', 'department_id' => $department->id, 'level' => '100']);
    $this->course = Courses::create(['code' => 'CSC 101', 'title' => 'Computing', 'credit_unit' => 3, 'semester' => 'First', 'level' => '100', 'department_id' => $department->id, 'academic_session_id' => $this->session->id]);
    RegistrationSetting::current()->update(['registration_open' => true, 'require_fee_clearance' => false, 'require_late_registration_fee' => true]);
    $this->actingAs($this->student);
});

it('blocks unpaid late registration and allows it without a fee when switched off', function () {
    $data = ['session' => $this->session->name, 'semester' => 'First', 'course_ids' => [$this->course->id]];
    $this->get(route('student.courses.registration'))->assertOk()->assertSee('late registration fee');
    $this->post(route('student.courses.register'), $data)->assertSessionHasErrors('late_registration');
    expect(CourseRegistration::count())->toBe(0)->and(Payment::count())->toBe(0);
    RegistrationSetting::current()->update(['require_late_registration_fee' => false]);
    $this->post(route('student.courses.register'), $data)->assertSessionHasNoErrors();
    expect(CourseRegistration::count())->toBe(1)->and(Payment::count())->toBe(0);
});

it('creates one 5000 naira charge per semester and accepts only verified payment', function () {
    $data = ['session' => $this->session->name, 'semester' => 'First'];
    $url = route('student.courses.late-payment');
    $this->post($url, $data)->assertRedirect();
    $payment = Payment::sole();
    $this->post($url, $data)->assertRedirect(route('payments.show', $payment));
    expect(Payment::count())->toBe(1)->and(LateRegistrationCharge::count())->toBe(1)->and($payment->amount)->toBe(500000);
    $this->get(route('payments.show', $payment))->assertOk()->assertSee('5,000.00');
    $checkoutUrl = 'https://checkout.paystack.com/late-registration-test';
    Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => [
        'authorization_url' => $checkoutUrl, 'reference' => $payment->reference,
    ]])]);
    $this->post(route('payments.checkout', $payment))->assertRedirect($checkoutUrl);
    $this->post(route('payments.checkout', $payment))->assertRedirect($checkoutUrl);
    Http::assertSent(fn ($request) => $request->url() === 'https://api.paystack.co/transaction/initialize'
        && $request['amount'] === 500000 && $request['currency'] === 'NGN'
        && $request['email'] === $this->student->email && $request['reference'] === $payment->reference
        && $request['callback_url'] === route('payments.callback')
        && $request['metadata']['purpose'] === 'late_registration');
    Http::assertSentCount(1);
    $this->post(route('student.courses.register'), $data + ['course_ids' => [$this->course->id]])->assertSessionHasErrors('late_registration');
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => [
        'reference' => $payment->reference, 'amount' => 500000, 'currency' => 'NGN', 'domain' => 'test',
        'customer' => ['email' => $payment->email], 'status' => 'success', 'id' => 12345, 'paid_at' => now()->toIso8601String(),
    ]])]);
    $this->get(route('payments.callback', ['reference' => $payment->reference]))
        ->assertRedirect(route('student.courses.registration', $data))
        ->assertSessionHas('success', 'Late registration payment confirmed. You can now register your courses.');
    app(PaystackPayments::class)->verify($payment->fresh());
    expect(LateRegistrationFee::paid($this->student, $this->session->name, 'First'))->toBeTrue()
        ->and(LateRegistrationFee::paid($this->student, $this->session->name, 'Second'))->toBeFalse()
        ->and(LateRegistrationFee::paid($this->student, '2024/2025', 'First'))->toBeFalse();
    $this->post(route('student.courses.register'), $data + ['course_ids' => [$this->course->id]])->assertSessionHasNoErrors();
    expect(CourseRegistration::count())->toBe(1)->and(Payment::count())->toBe(1);
});

it('rejects incorrect amounts and another students payment and disables checkout when the toggle is off', function () {
    $payment = LateRegistrationFee::payment($this->student, $this->session->name, 'First');
    $payment->update(['status' => 'success', 'verified_at' => now(), 'gateway_id' => '123', 'amount' => 100]);
    expect(LateRegistrationFee::paid($this->student, $this->session->name, 'First'))->toBeFalse();
    $other = User::factory()->create(['usertype' => 'student']);
    $payment->update(['amount' => 500000, 'user_id' => $other->id]);
    expect(LateRegistrationFee::paid($this->student, $this->session->name, 'First'))->toBeFalse();
    $payment->update(['user_id' => $this->student->id, 'status' => 'pending']);
    RegistrationSetting::current()->update(['require_late_registration_fee' => false]);
    expect(fn () => app(PaystackPayments::class)->checkout($payment->fresh()))->toThrow(RuntimeException::class);
    $this->post(route('student.courses.late-payment'), ['session' => $this->session->name, 'semester' => 'Second'])->assertSessionHasErrors('late_registration');
    expect(Payment::count())->toBe(1);
    Http::assertNothingSent();
});

it('saves the third toggle for admins and restricts students from changing it', function () {
    $admin = User::factory()->create(['usertype' => 'admin']);
    $data = ['registration_open' => 1, 'require_fee_clearance' => 0, 'require_late_registration_fee' => 0];
    $this->actingAs($admin)->get(route('admin.registration-settings.edit'))->assertOk()->assertSee('Late course registration');
    $this->put(route('admin.registration-settings.update'), $data)->assertSessionHasNoErrors();
    expect(RegistrationSetting::current()->require_late_registration_fee)->toBeFalse();
    $this->actingAs($this->student)->put(route('admin.registration-settings.update'), $data)->assertForbidden();
});
