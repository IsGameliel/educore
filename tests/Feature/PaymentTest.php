<?php

use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Payment;
use App\Models\ResultAppeal;
use App\Models\TranscriptRequest;
use App\Models\User;
use App\Services\PaystackPayments;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['services.paystack.secret_key' => 'sk_test_payments']);
    Http::preventStrayRequests();
    Storage::fake('local');
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $this->department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $this->student = User::factory()->create(['usertype' => 'student', 'department_id' => $this->department->id]);
    $this->admin = User::factory()->create(['usertype' => 'admin']);
    AcademicSession::create(['name' => '2025/2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
    Http::fake(['api.paystack.co/transaction/initialize' => fn ($request) => Http::response([
        'status' => true, 'data' => ['reference' => $request['reference'], 'authorization_url' => 'https://checkout.paystack.com/payment-test'],
    ])]);
});

function pendingTranscriptPayment(User $student): Payment
{
    $request = TranscriptRequest::create(['user_id' => $student->id, 'department_id' => $student->department_id, 'purpose' => 'Graduate school application', 'status' => 'awaiting_payment']);

    return app(PaystackPayments::class)->create($request, $student, 'transcript');
}

it('exports filtered payments safely and restricts financial reports to finance staff', function () {
    $payment = pendingTranscriptPayment($this->student);
    $payment->update(['email'=>'=unsafe@example.com']);
    $this->actingAs($this->admin)->get(route('finance.payments.export', ['status'=>'pending']))->assertOk()
        ->assertStreamedContent("Reference,Email,Purpose,Status,Currency,\"Gross kobo\",\"Deductions kobo\",\"Net kobo\",\"Paid at\"\n".$payment->reference.",'=unsafe@example.com,transcript,pending,NGN,3000000,0,0,\n");
    $this->actingAs($this->student)->get(route('finance.payments.export'))->assertForbidden();
});

function fakePaymentVerification(Payment $payment, array $overrides = []): void
{
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response([
        'status' => true, 'data' => array_replace_recursive([
            'id' => 900001, 'reference' => $payment->reference, 'status' => 'success',
            'amount' => $payment->amount, 'currency' => 'NGN', 'domain' => 'test',
            'customer' => ['email' => $payment->email], 'channel' => 'card', 'paid_at' => now()->toIso8601String(),
        ], $overrides),
    ])]);
}

it('charges the server transcript fee and reuses pending submissions and checkout', function () {
    $this->actingAs($this->student);
    foreach ([1, 2] as $attempt) {
        $this->post(route('academic.transcripts.request'), ['purpose' => 'Graduate school application', 'amount' => 1, 'status' => 'success'])
            ->assertRedirect('https://checkout.paystack.com/payment-test');
    }
    $payment = Payment::sole();
    expect($payment->amount)->toBe(3000000)->and($payment->status)->toBe('pending')
        ->and(TranscriptRequest::sole()->status)->toBe('awaiting_payment');
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request['amount'] === 3000000 && $request['currency'] === 'NGN');
    $this->get(route('payments.index'))->assertOk()->assertSee('30,000.00');
    $this->get(route('payments.show', $payment))->assertOk()->assertSee('Check payment status');
});

it('requires appeal payment before manager visibility or review', function () {
    $this->actingAs($this->student)->post(route('academic.appeals.store'), [
        'session' => '2025/2026', 'semester' => 'First', 'course_code' => 'PAY101',
        'message' => 'My examination result is missing.',
    ])->assertRedirect('https://checkout.paystack.com/payment-test');
    $appeal = ResultAppeal::sole();
    expect($appeal->status)->toBe('awaiting_payment')->and($appeal->payment->amount)->toBe(1500000);
    $this->get(route('academic.appeals'))->assertOk()->assertSee('Continue payment');
    $this->actingAs($this->admin)->get(route('academic.appeals'))->assertOk()->assertDontSee('PAY101');
    $this->post(route('academic.appeals.resolve', $appeal), ['status' => 'resolved', 'response' => 'This should be blocked.'])->assertSessionHasErrors('appeal');
    fakePaymentVerification($appeal->payment);
    $this->actingAs($this->student)->get(route('payments.callback', ['reference' => $appeal->payment->reference]))->assertRedirect(route('academic.appeals'));
    expect($appeal->fresh()->status)->toBe('open');
    $this->actingAs($this->admin)->get(route('academic.appeals'))->assertOk()->assertSee('PAY101');
});

it('prevents issuing or viewing unpaid transcript requests in the staff queue', function () {
    $payment = pendingTranscriptPayment($this->student);
    $this->actingAs($this->admin)->get(route('academic.transcripts'))->assertOk()->assertDontSee('Graduate school application');
    $this->post(route('academic.transcripts.decide', $payment->payable_id), ['decision' => 'issue', 'reason' => 'Attempt before paying'])->assertSessionHasErrors('request');
    expect($payment->payable->fresh()->status)->toBe('awaiting_payment');
});

it('submits paid transcript requests exactly once across callbacks and webhooks', function () {
    $payment = pendingTranscriptPayment($this->student);
    fakePaymentVerification($payment);
    $this->actingAs($this->student)->get(route('payments.callback', ['reference' => $payment->reference]))->assertRedirect(route('academic.transcripts'));
    $payment->payable->update(['status' => 'issued']);
    $this->get(route('payments.callback', ['reference' => $payment->reference]))->assertRedirect(route('academic.transcripts'));
    $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference]]);
    $this->call('POST', route('payments.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, 'sk_test_payments')], $body)->assertOk();
    expect($payment->fresh()->status)->toBe('success')->and($payment->payable->fresh()->status)->toBe('issued')
        ->and(TranscriptRequest::count())->toBe(1);
    Http::assertSentCount(1);
});

it('rejects successful gateway responses with mismatched payment details', function ($override) {
    $payment = pendingTranscriptPayment($this->student);
    fakePaymentVerification($payment, $override);
    $this->actingAs($this->student)->get(route('payments.callback', ['reference' => $payment->reference]))
        ->assertRedirect(route('payments.show', $payment))->assertSessionHasErrors('payment');
    expect($payment->fresh()->status)->toBe('pending')->and($payment->payable->status)->toBe('awaiting_payment');
})->with([
    'wrong amount' => [['amount' => 1]], 'wrong currency' => [['currency' => 'USD']],
    'wrong email' => [['customer' => ['email' => 'another@example.com']]],
    'wrong reference' => [['reference' => 'another-reference']], 'wrong environment' => [['domain' => 'live']],
]);

it('keeps failed and pending payments out of review', function ($status) {
    $payment = pendingTranscriptPayment($this->student);
    fakePaymentVerification($payment, ['status' => $status]);
    $this->actingAs($this->student)->get(route('payments.callback', ['reference' => $payment->reference]))->assertRedirect(route('payments.show', $payment));
    expect($payment->fresh()->status)->toBe($status)->and($payment->payable->status)->toBe('awaiting_payment');
})->with(['failed', 'abandoned', 'pending']);

it('requires valid webhook signatures and verifies payments without a browser session', function () {
    $payment = pendingTranscriptPayment($this->student);
    $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference]]);
    $this->postJson(route('payments.webhook'), json_decode($body, true))->assertUnauthorized();
    Http::assertNothingSent();
    fakePaymentVerification($payment);
    $this->call('POST', route('payments.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, 'sk_test_payments')], $body)->assertOk();
    expect($payment->fresh()->status)->toBe('success')->and($payment->payable->status)->toBe('pending');
});

it('returns a retryable webhook response when verification is unavailable', function () {
    $payment = pendingTranscriptPayment($this->student);
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response([], 503)]);
    $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference]]);
    $this->call('POST', route('payments.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, 'sk_test_payments')], $body)->assertStatus(503);
    expect($payment->fresh()->status)->toBe('pending');
});

it('blocks other users from seeing paying or confirming someone elses payment', function () {
    $payment = pendingTranscriptPayment($this->student);
    $other = User::factory()->create(['usertype' => 'student']);
    $this->actingAs($other)->get(route('payments.show', $payment))->assertForbidden();
    $this->post(route('payments.checkout', $payment))->assertForbidden();
    $this->post(route('payments.refresh', $payment))->assertForbidden();
    $this->get(route('payments.callback', ['reference' => $payment->reference]))->assertNotFound();
    $this->get(route('payments.index'))->assertOk()->assertDontSee('Graduate school application')->assertSee('No payments yet');
    $this->get(route('admin.payments.index'))->assertForbidden();
    Http::assertNothingSent();
});

it('preserves requests and displays recovery instructions if checkout is unavailable', function () {
    config(['services.paystack.secret_key' => null]);
    $this->actingAs($this->student)->post(route('academic.transcripts.request'), ['purpose' => 'Graduate school application'])->assertSessionHasErrors('payment');
    $payment = Payment::sole();
    expect($payment->status)->toBe('pending')->and($payment->payable->status)->toBe('awaiting_payment');
    $this->get(route('payments.show', $payment))->assertOk()->assertSee('Your details are saved');
    Http::assertNothingSent();
});

it('shows admin totals and filters without granting staff payment access', function () {
    $payment = pendingTranscriptPayment($this->student);
    fakePaymentVerification($payment);
    app(PaystackPayments::class)->verify($payment);
    $this->actingAs($this->admin)->get(route('admin.payments.index'))->assertOk()->assertSee('30,000.00')->assertSee($payment->reference);
    $this->get(route('admin.payments.index', ['status' => 'pending']))->assertOk()->assertDontSee($payment->reference);
    $this->get(route('admin.payments.index', ['search' => $this->student->email]))->assertOk()->assertSee($payment->reference);
    $this->get(route('admin.payments.index', ['purpose' => 'appeal']))->assertOk()->assertDontSee($payment->reference);
    $this->get(route('admin.payments.index', ['to' => now()->format('Y-m-d')]))->assertOk()->assertSee($payment->reference);
    $this->actingAs(User::factory()->create(['usertype' => 'exam_officer']))->get(route('admin.payments.index'))->assertForbidden();
});

it('requires student payment for transcript requests created by a manager', function () {
    $this->actingAs($this->admin)->post(route('academic.transcripts.request'), ['user_id' => $this->student->id, 'purpose' => 'Graduate school application'])->assertSessionHasNoErrors();
    $payment = Payment::sole();
    expect($payment->user_id)->toBe($this->student->id)->and($payment->payable->status)->toBe('awaiting_payment');
    $this->actingAs($this->student)->get(route('payments.index'))->assertOk()->assertSee('Continue payment');
    Http::assertNothingSent();
});

it('does not redirect to an untrusted checkout URL', function () {
    $payment = pendingTranscriptPayment($this->student);
    Http::swap(new \Illuminate\Http\Client\Factory);
    Http::preventStrayRequests();
    Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
        'status' => true, 'data' => ['reference' => $payment->reference, 'authorization_url' => 'https://example.com/fake-checkout'],
    ])]);
    $this->actingAs($this->student)->post(route('payments.checkout', $payment))
        ->assertRedirect(route('payments.show', $payment))->assertSessionHasErrors('payment');
    expect($payment->fresh()->authorization_url)->toBeNull();
});

it('does not downgrade a verified payment after another verification request', function () {
    $payment = pendingTranscriptPayment($this->student);
    fakePaymentVerification($payment);
    app(PaystackPayments::class)->verify($payment);
    Http::swap(new \Illuminate\Http\Client\Factory);
    Http::preventStrayRequests();
    fakePaymentVerification($payment, ['status' => 'failed']);
    $this->actingAs($this->student)->post(route('payments.refresh', $payment))->assertRedirect(route('academic.transcripts'));
    expect($payment->fresh()->status)->toBe('success');
});
