<?php

use App\Models\{AcademicSession, Department, Faculty, Payment, PaymentGatewayChange, TuitionInvoice, TuitionSchedule, User};
use App\Services\{PaymentFinancialSync, PaystackPayments, TuitionBilling};
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.paystack.secret_key' => 'sk_test_recovery']);
    Http::preventStrayRequests();
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $this->admin = User::factory()->create(['usertype' => 'admin']);
    $this->student = User::factory()->create(['usertype' => 'student', 'department_id' => $department->id, 'level' => '100', 'entry_year' => 2026]);
    $this->session = AcademicSession::create(['name' => '2026/2027', 'start_year' => 2026, 'end_year' => 2027, 'is_active' => true, 'tuition_enabled' => true]);
    $this->session->forceFill(['tuition_enabled' => true])->save();
    $schedule = TuitionSchedule::create(['academic_session_id' => $this->session->id, 'department_id' => $department->id,
        'level' => '100', 'category' => 'new', 'period' => 'Annual', 'items' => [['label' => 'Tuition', 'amount' => 20000000]],
        'amount' => 20000000, 'first_percent' => 100, 'due_date' => '2026-11-01', 'status' => 'published', 'created_by' => $this->admin->id]);
    $this->invoice = app(TuitionBilling::class)->issue($this->student, $schedule);
    $this->payment = app(TuitionBilling::class)->payment($this->invoice, 'full');
    $this->refunds = [];
    $this->disputes = [];
    $this->gatewayStatus = 'success';
    $this->gatewayHasPaidAt = true;
    $this->gatewayUnavailable = false;
    Http::fake(function ($request) {
        if ($this->gatewayUnavailable) { return Http::response([], 503); }
        $path = parse_url($request->url(), PHP_URL_PATH);
        if (str_starts_with($path, '/transaction/verify/')) {
            $payment = Payment::where('reference', rawurldecode(basename($path)))->firstOrFail();
            return Http::response(['status' => true, 'data' => ['id' => 100000 + $payment->id,
                'reference' => $payment->reference, 'amount' => $payment->amount, 'currency' => 'NGN', 'domain' => 'test',
                'status' => $this->gatewayStatus, 'paid_at' => $this->gatewayHasPaidAt ? now()->subHour()->toIso8601String() : null, 'customer' => ['email' => $payment->email]]]);
        }
        if ($path === '/refund') {
            $page = (int) ($request['page'] ?? 1);
            return Http::response(['status' => true, 'data' => array_slice(array_values($this->refunds), ($page - 1) * 100, 100),
                'meta' => ['pageCount' => (int) ceil(count($this->refunds) / 100)]]);
        }
        if (str_starts_with($path, '/refund/')) {
            return Http::response(['status' => true, 'data' => $this->refunds[basename($path)] ?? []]);
        }
        if ($path === '/dispute') {
            return Http::response(['status' => true, 'data' => array_values($this->disputes)]);
        }
        if (str_starts_with($path, '/transaction/')) {
            return Http::response(['status' => true, 'data' => ['reference' => $this->payment->reference]]);
        }
        if (str_starts_with($path, '/dispute/')) {
            return Http::response(['status' => true, 'data' => $this->disputes[basename($path)] ?? []]);
        }
        throw new RuntimeException('Unexpected gateway request: '.$path);
    });
    app(PaystackPayments::class)->verify($this->payment);
    $this->payment->refresh();
});

function recoveryRefund($test, int $id = 501, array $overrides = []): array
{
    return $test->refunds[$id] = array_replace(['id' => $id, 'transaction' => (int) $test->payment->gateway_id,
        'domain' => 'test', 'currency' => 'NGN', 'amount' => 5000000, 'status' => 'processed', 'dispute' => null,
        'updatedAt' => '2026-10-04T12:00:00Z'], $overrides);
}

function recoveryDispute($test, array $overrides = []): array
{
    return $test->disputes[601] = array_replace(['id' => 601, 'transaction' => ['id' => (int) $test->payment->gateway_id,
        'reference' => $test->payment->reference, 'currency' => 'NGN'], 'domain' => 'test', 'currency' => 'NGN',
        'refund_amount' => 5000000, 'status' => 'pending', 'resolution' => null, 'updatedAt' => '2026-10-04T12:00:00Z'], $overrides);
}

function recoveryWebhook($test, string $event, int $id)
{
    // Amount/status in the webhook are deliberately untrusted; only the API response is applied.
    $body = json_encode(['event' => $event, 'data' => ['id' => $id, 'amount' => 1, 'status' => 'failed']]);
    return $test->call('POST', route('payments.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json',
        'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, 'sk_test_recovery')], $body);
}

it('deducts a verified partial refund once across duplicate and delayed webhooks and reopens clearance', function () {
    expect(app(TuitionBilling::class)->clearance($this->student, $this->session, 'First')['cleared'])->toBeTrue();
    recoveryRefund($this);
    recoveryWebhook($this, 'refund.processed', 501)->assertOk();
    recoveryWebhook($this, 'refund.processed', 501)->assertOk();
    recoveryWebhook($this, 'refund.pending', 501)->assertOk();
    expect($this->invoice->fresh()->totals()['balance'])->toBe(5000000)
        ->and(PaymentGatewayChange::count())->toBe(1)->and($this->payment->fresh()->status)->toBe('success')
        ->and(app(TuitionBilling::class)->clearance($this->student, $this->session, 'First')['cleared'])->toBeFalse();
    $this->actingAs($this->student)->get(route('payments.receipt', $this->payment))->assertOk();
});

it('does not deduct pending processing or failed refunds', function ($status) {
    recoveryRefund($this, 501, ['status' => $status]);
    recoveryWebhook($this, 'refund.'.$status, 501)->assertOk();
    expect($this->invoice->fresh()->totals()['balance'])->toBe(0);
})->with(['pending', 'processing', 'failed']);

it('sums partial refunds without treating a reversed transaction status as a full refund', function () {
    recoveryRefund($this);
    recoveryRefund($this, 502, ['amount' => 3000000]);
    app(PaymentFinancialSync::class)->reconcile($this->payment);
    expect($this->invoice->fresh()->totals()['balance'])->toBe(8000000);
    $this->gatewayStatus = 'reversed';
    app(PaystackPayments::class)->verify($this->payment, true);
    expect($this->invoice->fresh()->totals()['balance'])->toBe(8000000);
    $this->gatewayStatus = 'success';
    app(PaystackPayments::class)->verify($this->payment->fresh(), true);
    expect($this->invoice->fresh()->totals()['balance'])->toBe(8000000);
});

it('holds clearance during a dispute without inventing debt and releases a declined dispute', function () {
    recoveryDispute($this);
    recoveryWebhook($this, 'charge.dispute.create', 601)->assertOk();
    expect($this->invoice->fresh()->totals()['balance'])->toBe(0)
        ->and(app(TuitionBilling::class)->clearance($this->student, $this->session, 'First')['cleared'])->toBeFalse();
    recoveryDispute($this, ['status' => 'resolved', 'resolution' => 'declined', 'updatedAt' => '2026-10-04T13:00:00Z']);
    recoveryWebhook($this, 'charge.dispute.resolve', 601)->assertOk();
    expect(app(TuitionBilling::class)->clearance($this->student, $this->session, 'First')['cleared'])->toBeTrue();
    recoveryDispute($this); // Stale API response must not undo a newer resolution.
    recoveryWebhook($this, 'charge.dispute.create', 601)->assertOk();
    expect($this->payment->fresh()->dispute_open)->toBeFalse();
});

it('counts an accepted dispute and its linked refund only once', function () {
    recoveryDispute($this, ['status' => 'resolved', 'resolution' => 'merchant-accepted']);
    recoveryWebhook($this, 'charge.dispute.resolve', 601)->assertOk();
    recoveryRefund($this, 501, ['dispute' => 601]);
    recoveryWebhook($this, 'refund.processed', 501)->assertOk();
    expect($this->invoice->fresh()->totals()['balance'])->toBe(5000000)->and($this->payment->fresh()->dispute_open)->toBeFalse();
});

it('rejects mismatched financial data without altering money', function ($override) {
    recoveryRefund($this, 501, $override);
    recoveryWebhook($this, 'refund.processed', 501)->assertStatus(503);
    expect(PaymentGatewayChange::count())->toBe(0)->and($this->invoice->fresh()->totals()['balance'])->toBe(0);
})->with([[['currency' => 'USD']], [['domain' => 'live']], [['amount' => 20000001]], [['id' => 777]], [['updatedAt' => null]]]);

it('requires signatures and returns retryable failures without changing financial state', function () {
    $this->postJson(route('payments.webhook'), ['event' => 'refund.processed', 'data' => ['id' => 501]])->assertUnauthorized();
    $this->gatewayUnavailable = true;
    recoveryWebhook($this, 'refund.processed', 501)->assertStatus(503);
    expect($this->invoice->fresh()->totals()['balance'])->toBe(0);
});

it('recovers missed refunds and disputes through scheduled reconciliation', function () {
    recoveryRefund($this);
    recoveryDispute($this);
    $this->artisan('payments:reconcile')->assertSuccessful();
    expect($this->invoice->fresh()->totals()['balance'])->toBe(5000000)
        ->and($this->payment->fresh()->dispute_open)->toBeTrue()->and($this->payment->fresh()->financial_checked_at)->not->toBeNull();
});

it('recovers late success on failed abandoned and reversed attempts without creating new charges', function ($status) {
    $this->payment->update(['status' => $status, 'gateway_id' => null, 'created_at' => now()->subHour(), 'verified_at' => now()->subHour()]);
    $this->payment->payable->update(['status' => 'superseded']);
    $this->artisan('payments:reconcile')->assertSuccessful();
    expect($this->payment->fresh()->status)->toBe('success')->and(Payment::count())->toBe(1)
        ->and($this->invoice->fresh()->totals()['balance'])->toBe(0);
    Http::assertNotSent(fn ($request) => $request->method() === 'POST');
})->with(['failed', 'abandoned', 'reversed']);

it('keeps failed verification attempts distinct from gateway verified failures', function () {
    $this->payment->update(['status' => 'failed', 'verified_at' => null, 'created_at' => now()->subHour()]);
    $this->gatewayUnavailable = true;
    $this->artisan('payments:reconcile')->assertSuccessful();
    expect($this->payment->fresh()->verified_at)->toBeNull()->and($this->payment->fresh()->reconciliation_attempted_at)->not->toBeNull();
});

it('allows fresh attempts after a verified abandoned or reversed charge and still credits late success', function ($status) {
    $this->gatewayStatus = $status;
    $this->gatewayHasPaidAt = false;
    $this->payment->update(['status' => $status, 'verified_at' => now()]);
    $this->payment->payable->update(['status' => 'awaiting_payment']);
    $replacement = app(TuitionBilling::class)->payment($this->invoice, 'full');
    $this->gatewayStatus = 'success';
    $this->gatewayHasPaidAt = true;
    app(PaystackPayments::class)->verify($replacement);
    app(PaystackPayments::class)->verify($this->payment->fresh());
    expect(Payment::count())->toBe(2)->and($this->invoice->fresh()->totals()['overpayment'])->toBe(20000000);
})->with(['abandoned', 'reversed']);

it('verifies manual refunds and deduplicates a webhook against a legacy adjustment', function () {
    $this->invoice->adjustments()->create(['type' => 'refund', 'amount' => 5000000, 'reason' => 'Previously recorded refund', 'external_reference' => '501', 'recorded_by' => $this->admin->id]);
    recoveryRefund($this);
    recoveryWebhook($this, 'refund.processed', 501)->assertOk();
    $this->actingAs($this->admin)->post(route('finance.invoices.adjust', $this->invoice), [
        'type' => 'refund', 'amount' => '50000', 'external_reference' => '501', 'reason' => 'Verify the completed refund.',
    ])->assertSessionHasNoErrors();
    expect($this->invoice->fresh()->totals()['balance'])->toBe(5000000)->and($this->invoice->adjustments()->count())->toBe(1);
    $this->post(route('finance.invoices.adjust', $this->invoice), ['type' => 'refund', 'amount' => '60000',
        'external_reference' => '501', 'reason' => 'Wrong amount must be rejected.'])->assertSessionHasErrors('external_reference');
});

it('shows the refund and dispute state on student and finance pages', function () {
    recoveryRefund($this);
    recoveryDispute($this);
    app(PaymentFinancialSync::class)->reconcile($this->payment);
    $this->actingAs($this->student)->get(route('tuition.show', $this->invoice))->assertOk()->assertSee('Refunds and disputes');
    $this->get(route('payments.show', $this->payment))->assertOk()->assertSee('A dispute is under review');
    $this->actingAs($this->admin)->get(route('finance.invoices.show', $this->invoice))->assertOk()->assertSee('Refunds and disputes');
});

it('processes every refund page and records a completed financial check', function () {
    for ($id = 501; $id <= 601; $id++) { recoveryRefund($this, $id, ['amount' => 100]); }
    app(PaymentFinancialSync::class)->reconcile($this->payment);
    expect(PaymentGatewayChange::count())->toBe(101)->and($this->invoice->fresh()->totals()['balance'])->toBe(10100);
    Http::assertSent(fn ($request) => str_contains($request->url(), '/refund?') && (int) $request['page'] === 2);
});

it('recovers a refund delivered before the original payment callback', function () {
    recoveryRefund($this);
    $this->payment->update(['status' => 'pending', 'gateway_id' => null]);
    $this->payment->payable->update(['status' => 'awaiting_payment']);
    recoveryWebhook($this, 'refund.processed', 501)->assertOk();
    expect($this->payment->fresh()->status)->toBe('success')->and($this->invoice->fresh()->totals()['balance'])->toBe(5000000);
});

it('does not mutate an invoice when a refund is manually submitted for another invoice', function () {
    recoveryRefund($this);
    $other = $this->invoice->replicate();
    $other->number = 'OTHER-INVOICE';
    $other->period = 'Second';
    $other->save();
    $this->actingAs($this->admin)->post(route('finance.invoices.adjust', $other), ['type' => 'refund', 'amount' => '50000',
        'external_reference' => '501', 'reason' => 'This belongs to a different invoice.'])->assertSessionHasErrors('external_reference');
    expect(PaymentGatewayChange::count())->toBe(0)->and($this->invoice->fresh()->totals()['balance'])->toBe(0);
});

it('pauses checkout until a bursar-reviewed legacy refund is matched and counted once', function () {
    $legacy = $this->invoice->adjustments()->create(['type' => 'refund', 'amount' => 5000000,
        'external_reference' => 'OLD-BURSARY-REFERENCE', 'reason' => 'Historical recorded refund.', 'recorded_by' => $this->admin->id]);
    recoveryRefund($this);
    recoveryWebhook($this, 'refund.processed', 501)->assertOk();
    expect($this->invoice->fresh()->totals()['refund_review'])->toBeTrue();
    $this->actingAs($this->student)->post(route('tuition.pay', $this->invoice), ['option' => 'full'])->assertSessionHasErrors('payment');
    $this->actingAs($this->admin)->post(route('finance.invoices.adjust', $this->invoice), ['type' => 'refund', 'amount' => '50000',
        'external_reference' => '501', 'legacy_adjustment_id' => $legacy->id, 'reason' => 'Match the historical refund to Paystack.'])->assertSessionHasNoErrors();
    expect($this->invoice->fresh()->totals()['refund_review'])->toBeFalse()
        ->and($this->invoice->fresh()->totals()['balance'])->toBe(5000000)->and($this->invoice->adjustments()->count())->toBe(1);
});

it('enforces refunded balances and open disputes on registration and keeps explicit exemptions', function () {
    recoveryDispute($this);
    recoveryWebhook($this, 'charge.dispute.create', 601)->assertOk();
    expect(fn () => app(TuitionBilling::class)->assertCleared($this->student, $this->session->name, 'First'))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
    \App\Models\TuitionClearance::create(['user_id' => $this->student->id, 'academic_session_id' => $this->session->id,
        'semester' => 'First', 'reason' => 'Approved exemption during review.', 'expires_on' => today()->addDay(), 'approved_by' => $this->admin->id]);
    expect(app(TuitionBilling::class)->clearance($this->student, $this->session, 'First')['cleared'])->toBeTrue();
    expect(app(TuitionBilling::class)->clearance($this->student, $this->session, 'Second')['cleared'])->toBeFalse();
});

it('holds unexplained reversals and recovers the original paid amount before a partial refund', function () {
    $this->gatewayStatus = 'reversed';
    $this->payment->update(['status' => 'pending', 'gateway_id' => null, 'paid_at' => null]);
    $this->payment->payable->update(['status' => 'awaiting_payment']);
    app(PaystackPayments::class)->verify($this->payment->fresh());
    expect($this->invoice->fresh()->totals()['reversal_review'])->toBeTrue()
        ->and(app(TuitionBilling::class)->clearance($this->student, $this->session, 'First')['cleared'])->toBeFalse();
    $this->payment->refresh();
    recoveryRefund($this);
    recoveryWebhook($this, 'refund.processed', 501)->assertOk();
    expect($this->invoice->fresh()->totals()['paid'])->toBe(15000000)
        ->and($this->invoice->fresh()->totals()['balance'])->toBe(5000000)
        ->and($this->invoice->fresh()->totals()['reversal_review'])->toBeFalse();
});

it('reopens the entire balance for a verified full refund', function () {
    recoveryRefund($this, 501, ['amount' => 20000000]);
    recoveryWebhook($this, 'refund.processed', 501)->assertOk();
    expect($this->invoice->fresh()->totals()['paid'])->toBe(0)->and($this->invoice->fresh()->totals()['balance'])->toBe(20000000);
});

it('retains subsecond ordering when gateway financial responses arrive out of order', function () {
    recoveryRefund($this, 501, ['updatedAt' => '2026-10-04T12:00:00.900Z']);
    recoveryWebhook($this, 'refund.processed', 501)->assertOk();
    recoveryRefund($this, 501, ['status' => 'pending', 'updatedAt' => '2026-10-04T12:00:00.100Z']);
    recoveryWebhook($this, 'refund.pending', 501)->assertOk();
    expect($this->invoice->fresh()->totals()['balance'])->toBe(5000000);
});

it('permits replacement of a reversed attempt that never received successful credit', function () {
    $this->gatewayStatus = 'reversed';
    $this->gatewayHasPaidAt = false;
    $this->payment->update(['status' => 'reversed', 'verified_at' => now(), 'gateway_reversed' => true, 'paid_at' => null]);
    $this->payment->payable->update(['status' => 'awaiting_payment']);
    $next = app(TuitionBilling::class)->payment($this->invoice, 'full');
    expect($next->id)->not->toBe($this->payment->id)->and($this->invoice->fresh()->totals()['reversal_review'])->toBeFalse();
});
