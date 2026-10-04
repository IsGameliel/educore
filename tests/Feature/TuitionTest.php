<?php

use App\Models\{AcademicSession, Courses, Department, Faculty, Payment, TuitionAdjustment, TuitionCharge, TuitionClearance, TuitionInvoice, TuitionSchedule, User};
use App\Services\{PaystackPayments, TuitionBilling};
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.paystack.secret_key' => 'sk_test_tuition']);
    Http::preventStrayRequests();
    Http::fake(['api.paystack.co/transaction/initialize' => fn ($request) => Http::response([
        'status' => true, 'data' => ['reference' => $request['reference'], 'authorization_url' => 'https://checkout.paystack.com/tuition-test'],
    ])]);
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $this->department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $this->session = AcademicSession::create(['name' => '2026/2027', 'start_year' => 2026, 'end_year' => 2027, 'is_active' => true]);
    $this->student = User::factory()->create(['usertype' => 'student', 'department_id' => $this->department->id, 'level' => '100', 'entry_year' => 2026]);
    $this->admin = User::factory()->create(['usertype' => 'admin']);
    $this->bursar = User::factory()->create(['usertype' => 'bursar']);
    $this->accountant = User::factory()->create(['usertype' => 'accountant']);
    $this->scheduleData = ['academic_session_id' => $this->session->id, 'department_id' => $this->department->id,
        'level' => '100', 'category' => 'new', 'period' => 'Annual', 'first_percent' => 100,
        'due_date' => '2026-11-01', 'labels' => ['Tuition', 'Library'], 'amounts' => ['190000.00', '10000.00']];
});

function tuitionFixture($test, array $overrides = []): TuitionInvoice
{
    $test->actingAs($test->admin)->post(route('finance.schedules.store'), array_replace($test->scheduleData, $overrides))->assertSessionHasNoErrors();
    $schedule = TuitionSchedule::latest('id')->firstOrFail();
    $test->post(route('finance.schedules.publish', $schedule))->assertSessionHasNoErrors();
    return TuitionInvoice::where('user_id', $test->student->id)->latest('id')->firstOrFail();
}

function verifyTuitionPayment(Payment $payment): void
{
    Http::swap(new \Illuminate\Http\Client\Factory);
    Http::preventStrayRequests();
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response([
        'status' => true, 'data' => ['id' => 100000 + $payment->id, 'reference' => $payment->reference,
            'amount' => $payment->amount, 'currency' => 'NGN', 'domain' => 'test', 'status' => 'success',
            'channel' => 'card', 'customer' => ['email' => $payment->email]],
    ])]);
    app(PaystackPayments::class)->verify($payment);
}

it('creates drafts and publishes immutable invoices only for the matching cohort', function () {
    $other = User::factory()->create(['usertype' => 'student', 'department_id' => $this->department->id, 'level' => '200', 'entry_year' => 2025]);
    $this->actingAs($this->bursar)->post(route('finance.schedules.store'), $this->scheduleData)->assertSessionHasNoErrors();
    expect(TuitionInvoice::count())->toBe(0);
    $schedule = TuitionSchedule::sole();
    $this->post(route('finance.schedules.publish', $schedule))->assertForbidden();
    $this->actingAs($this->admin)->post(route('finance.schedules.publish', $schedule))->assertSessionHasNoErrors();
    $invoice = TuitionInvoice::sole();
    expect($invoice->amount)->toBe(20000000)->and($invoice->user_id)->toBe($this->student->id);
    $this->post(route('finance.schedules.publish', $schedule))->assertSessionHasNoErrors();
    $this->put(route('finance.schedules.update', $schedule), $this->scheduleData)->assertSessionHasNoErrors();
    expect(TuitionInvoice::count())->toBe(1);
    $this->student->update(['level' => '200']);
    app(TuitionBilling::class)->ensureInvoices($this->student, $this->session);
    expect($invoice->fresh()->level)->toBe('100')->and($invoice->fresh()->amount)->toBe(20000000);
});

it('allows bursars to edit drafts and rejects duplicate schedules', function () {
    $this->actingAs($this->bursar)->post(route('finance.schedules.store'), $this->scheduleData)->assertSessionHasNoErrors();
    $schedule = TuitionSchedule::sole();
    $this->put(route('finance.schedules.update', $schedule), array_replace($this->scheduleData, ['amounts' => ['180000.01', '10000']]))->assertSessionHasNoErrors();
    expect($schedule->fresh()->amount)->toBe(19000001);
    $this->get(route('finance.schedules', ['edit' => $schedule->id]))->assertOk()->assertSee('180000.01');
    $this->post(route('finance.schedules.store'), $this->scheduleData)->assertSessionHasErrors('period');
});

it('edits published fees for future invoices while preserving billed students and payments', function () {
    $invoice = tuitionFixture($this);
    $schedule = TuitionSchedule::sole();
    $payment = app(TuitionBilling::class)->payment($invoice, 'full');
    verifyTuitionPayment($payment);
    $this->get(route('finance.schedules', ['edit' => $schedule->id]))->assertOk()->assertSee('Edit published schedule')->assertSee('Save changes');
    $this->put(route('finance.schedules.update', $schedule), array_replace($this->scheduleData, [
        'amounts' => ['290000', '10000'], 'first_percent' => 60, 'due_date' => '2026-12-01', 'second_due_date' => '2027-04-01',
    ]))->assertSessionHasNoErrors();
    expect($schedule->fresh()->status)->toBe('published')->and($schedule->fresh()->amount)->toBe(30000000);
    expect($invoice->fresh()->amount)->toBe(20000000)->and($invoice->fresh()->first_percent)->toBe(100)
        ->and($invoice->fresh()->due_date->format('Y-m-d'))->toBe('2026-11-01')->and($payment->fresh()->amount)->toBe(20000000);
    $newStudent = User::factory()->create(['usertype'=>'student', 'department_id'=>$this->department->id, 'level'=>'100', 'entry_year'=>2026]);
    app(TuitionBilling::class)->ensureInvoices($newStudent, $this->session);
    expect(TuitionInvoice::where('user_id', $newStudent->id)->sole()->amount)->toBe(30000000);
    $this->put(route('finance.schedules.update', $schedule), array_replace($this->scheduleData, ['level'=>'200']))->assertSessionHasErrors('level');
});

it('reserves published edits and all deletions for admins', function () {
    tuitionFixture($this);
    $schedule = TuitionSchedule::sole();
    foreach ([$this->bursar, $this->accountant, $this->student] as $user) {
        $this->actingAs($user)->get(route('finance.schedules', ['edit'=>$schedule->id]))->assertForbidden();
        $this->put(route('finance.schedules.update', $schedule), $this->scheduleData)->assertForbidden();
        $this->delete(route('finance.schedules.destroy', $schedule))->assertForbidden();
    }
    expect($schedule->fresh()->deleted_at)->toBeNull();
});

it('deletes published schedules while retaining payments and allows a replacement without rebilling', function () {
    $invoice = tuitionFixture($this);
    $schedule = TuitionSchedule::sole();
    $payment = app(TuitionBilling::class)->payment($invoice, 'full');
    verifyTuitionPayment($payment);
    $this->delete(route('finance.schedules.destroy', $schedule))->assertRedirect(route('finance.schedules'));
    $this->assertSoftDeleted('tuition_schedules', ['id'=>$schedule->id]);
    expect(TuitionSchedule::count())->toBe(0)->and($invoice->fresh()->totals()['paid'])->toBe(20000000);
    $newStudent = User::factory()->create(['usertype'=>'student', 'department_id'=>$this->department->id, 'level'=>'100', 'entry_year'=>2026]);
    app(TuitionBilling::class)->ensureInvoices($newStudent, $this->session);
    expect(TuitionInvoice::count())->toBe(1);
    $this->post(route('finance.schedules.store'), $this->scheduleData)->assertSessionHasNoErrors();
    $replacement = TuitionSchedule::sole();
    $this->post(route('finance.schedules.publish', $replacement))->assertSessionHasNoErrors();
    expect(TuitionInvoice::count())->toBe(2)->and($invoice->fresh()->tuition_schedule_id)->toBe($schedule->id);
    $this->delete(route('finance.schedules.destroy', $schedule))->assertNotFound();
    $this->get(route('finance.schedules', ['edit'=>$schedule->id]))->assertNotFound();
    $this->actingAs($this->student)->get(route('tuition.show', $invoice))->assertOk();
});

it('deletes drafts and prevents annual and semester rebilling after replacement', function () {
    $invoice = tuitionFixture($this);
    $this->delete(route('finance.schedules.destroy', TuitionSchedule::sole()))->assertSessionHasNoErrors();
    $this->post(route('finance.schedules.store'), array_replace($this->scheduleData, ['period'=>'First']))->assertSessionHasNoErrors();
    $draft = TuitionSchedule::sole();
    $this->actingAs($this->bursar)->delete(route('finance.schedules.destroy', $draft))->assertForbidden();
    $this->actingAs($this->admin)->delete(route('finance.schedules.destroy', $draft))->assertSessionHasNoErrors();
    $this->post(route('finance.schedules.store'), array_replace($this->scheduleData, ['period'=>'First']))->assertSessionHasNoErrors();
    $this->post(route('finance.schedules.publish', TuitionSchedule::sole()))->assertSessionHasNoErrors();
    expect(TuitionInvoice::count())->toBe(1)->and($invoice->fresh()->period)->toBe('Annual');
});

it('removes deleted schedules from student balances and blocks new and resumed checkout', function () {
    $invoice = tuitionFixture($this);
    $this->session->forceFill(['tuition_enabled'=>true])->save();
    $payment = app(TuitionBilling::class)->payment($invoice, 'full');
    $payment->update(['authorization_url'=>'https://checkout.paystack.com/old-checkout']);
    $this->delete(route('finance.schedules.destroy', TuitionSchedule::sole()))->assertSessionHasNoErrors();
    $this->actingAs($this->student)->get(route('tuition.index'))->assertOk()->assertDontSee($invoice->number)->assertDontSee('200,000.00');
    $this->get(route('dashboard'))->assertOk()->assertDontSee('200,000.00');
    $this->get(route('tuition.show', $invoice))->assertOk()->assertSee('schedule has been withdrawn')->assertDontSee('Continue to Paystack');
    $this->post(route('tuition.pay', $invoice), ['option'=>'full'])->assertSessionHasErrors('tuition');
    $this->post(route('payments.checkout', $payment))->assertRedirect(route('payments.show', $payment))->assertSessionHasErrors('payment');
    $this->get(route('payments.show', $payment))->assertOk()->assertSee('Tuition schedule withdrawn')->assertDontSee('with Paystack');
    Http::assertNothingSent();
    expect(TuitionInvoice::count())->toBe(1)->and(Payment::count())->toBe(1);
    $clearance = app(TuitionBilling::class)->clearance($this->student, $this->session->fresh(), 'First');
    expect($clearance['cleared'])->toBeFalse()->and($clearance['message'])->toContain('withdrawn');
    // An already-issued gateway checkout can settle later; preserve and verify its evidence.
    verifyTuitionPayment($payment);
    $this->get(route('payments.index'))->assertOk()->assertViewHas('payments', fn ($payments) => $payments->contains('id', $payment->id));
    $this->get(route('payments.receipt', $payment))->assertOk()->assertHeader('content-type', 'application/pdf');
    expect(app(TuitionBilling::class)->clearance($this->student, $this->session->fresh(), 'First')['cleared'])->toBeTrue();
});

it('rejects overlapping annual and semester fees', function () {
    tuitionFixture($this);
    $this->post(route('finance.schedules.store'), array_replace($this->scheduleData, ['period' => 'First']))->assertSessionHasNoErrors();
    $this->post(route('finance.schedules.publish', TuitionSchedule::latest('id')->first()))->assertSessionHasErrors('schedule');
    expect(TuitionInvoice::count())->toBe(1);
});

it('requires coverage before enabling tuition clearance and blocks unpaid registration', function () {
    $this->actingAs($this->admin)->post(route('finance.sessions.enable', $this->session))->assertSessionHasErrors('session');
    $invoice = tuitionFixture($this);
    $this->post(route('finance.sessions.enable', $this->session))->assertSessionHasNoErrors();
    $course = Courses::create(['code' => 'TU101', 'title' => 'Tuition test course', 'credit_unit' => 3, 'department_id' => $this->department->id, 'level' => '100', 'semester' => 'First', 'academic_session_id' => $this->session->id]);
    $this->actingAs($this->student)->post(route('student.courses.register'), ['semester' => 'First', 'course_ids' => [$course->id]])->assertSessionHasErrors('tuition');
    $this->actingAs($this->admin)->put(route('admin.course-registrations.update', $this->student), ['session' => $this->session->name, 'semester' => 'First', 'course_ids' => [$course->id]])->assertSessionHasErrors('tuition');
    $payment = app(TuitionBilling::class)->payment($invoice, 'full');
    verifyTuitionPayment($payment);
    $this->actingAs($this->student)->post(route('student.courses.register'), ['semester' => 'First', 'course_ids' => [$course->id]])->assertSessionHasNoErrors();
    expect($invoice->fresh()->totals()['balance'])->toBe(0);
});

it('blocks enabling clearance when a student has no matching fees', function () {
    tuitionFixture($this);
    User::factory()->create(['usertype' => 'student', 'department_id' => $this->department->id, 'level' => '300']);
    $this->post(route('finance.sessions.enable', $this->session))->assertSessionHasErrors('session');
    expect($this->session->fresh()->tuition_enabled)->toBeFalse();
});

it('supports installment clearance and credits a callback or webhook only once', function () {
    $invoice = tuitionFixture($this, ['first_percent' => 60, 'second_due_date' => '2027-04-01']);
    $this->post(route('finance.sessions.enable', $this->session))->assertSessionHasNoErrors();
    $billing = app(TuitionBilling::class);
    $payment = $billing->payment($invoice, 'installment');
    expect($payment->amount)->toBe(12000000);
    $payment->update(['initialization_attempted_at'=>now()]);
    expect($billing->payment($invoice, 'full')->id)->toBe($payment->id);
    verifyTuitionPayment($payment);
    $this->actingAs($this->student)->get(route('payments.callback', ['reference' => $payment->reference]))->assertRedirect(route('tuition.show', $invoice));
    $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference]]);
    $this->call('POST', route('payments.webhook'), [], [], [], ['CONTENT_TYPE'=>'application/json', 'HTTP_X_PAYSTACK_SIGNATURE'=>hash_hmac('sha512', $body, 'sk_test_tuition')], $body)->assertOk();
    expect($billing->clearance($this->student, $this->session->fresh(), 'First')['cleared'])->toBeTrue()
        ->and($billing->clearance($this->student, $this->session->fresh(), 'Second')['cleared'])->toBeFalse()
        ->and($invoice->fresh()->totals()['balance'])->toBe(8000000);
    $second = $billing->payment($invoice, 'full');
    expect($second->amount)->toBe(8000000)->and($second->reference)->not->toBe($payment->reference);
    verifyTuitionPayment($second);
    expect($billing->clearance($this->student, $this->session->fresh(), 'Second')['cleared'])->toBeTrue()
        ->and($invoice->fresh()->totals()['paid'])->toBe(20000000);
});

it('bills semesters separately and uses the matching semester for clearance', function () {
    $first = tuitionFixture($this, ['period' => 'First']);
    $this->post(route('finance.sessions.enable', $this->session))->assertSessionHasErrors('session');
    $second = tuitionFixture($this, ['period' => 'Second']);
    $this->post(route('finance.sessions.enable', $this->session))->assertSessionHasNoErrors();
    verifyTuitionPayment(app(TuitionBilling::class)->payment($first, 'full'));
    expect(app(TuitionBilling::class)->clearance($this->student, $this->session->fresh(), 'First')['cleared'])->toBeTrue()
        ->and(app(TuitionBilling::class)->clearance($this->student, $this->session->fresh(), 'Second')['cleared'])->toBeFalse();
});

it('ignores client amounts and renders the student tuition pages and dashboard', function () {
    $invoice = tuitionFixture($this);
    $this->actingAs($this->student)->post(route('tuition.pay', $invoice), ['option'=>'full', 'amount'=>1])->assertRedirect('https://checkout.paystack.com/tuition-test');
    expect(Payment::sole()->amount)->toBe(20000000);
    $this->get(route('tuition.index'))->assertOk()->assertSee('200,000.00')->assertSee('Tuition &amp; Balance', false);
    $this->get(route('tuition.show', $invoice))->assertOk()->assertSee($invoice->number);
    $this->get(route('dashboard'))->assertOk()->assertSee('Outstanding balance');
});

it('protects invoices payments and receipts from other students', function () {
    $invoice = tuitionFixture($this);
    $payment = app(TuitionBilling::class)->payment($invoice, 'full');
    $other = User::factory()->create(['usertype'=>'student']);
    $this->actingAs($other)->get(route('tuition.show', $invoice))->assertForbidden();
    $this->post(route('tuition.pay', $invoice), ['option'=>'full'])->assertForbidden();
    $this->get(route('payments.receipt', $payment))->assertForbidden();
    $this->get(route('finance.invoices'))->assertForbidden();
    $this->actingAs($this->student)->get(route('payments.receipt', $payment))->assertNotFound();
});

it('generates a downloadable receipt only for verified payments', function () {
    $invoice = tuitionFixture($this);
    $payment = app(TuitionBilling::class)->payment($invoice, 'full');
    verifyTuitionPayment($payment);
    $this->actingAs($this->student)->get(route('payments.receipts'))->assertOk()->assertSee($payment->receiptNumber());
    $this->get(route('payments.receipt', $payment))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->actingAs($this->accountant)->get(route('payments.receipt', $payment))->assertOk();
});

it('records scholarships and completed refunds without altering payment evidence', function () {
    $invoice = tuitionFixture($this);
    $this->post(route('finance.invoices.adjust', $invoice), ['type'=>'scholarship','amount'=>'20000','reason'=>'Approved scholarship for academic merit.'])->assertSessionHasNoErrors();
    expect($invoice->fresh()->totals()['due'])->toBe(20000000);
    $reviewer = User::factory()->create(['usertype'=>'admin']);
    $this->actingAs($reviewer)->post(route('finance.approvals.review', \App\Models\FinancialApproval::sole()), ['decision'=>'approved','reason'=>'Independently verified scholarship award.'])->assertSessionHasNoErrors();
    $payment = app(TuitionBilling::class)->payment($invoice, 'full');
    expect($payment->amount)->toBe(18000000);
    verifyTuitionPayment($payment);
    Http::fake(['api.paystack.co/refund/501' => Http::response(['status' => true, 'data' => [
        'id' => 501, 'transaction' => 100000 + $payment->id, 'domain' => 'test', 'currency' => 'NGN',
        'amount' => 1000000, 'status' => 'processed', 'updatedAt' => now()->toIso8601String(),
    ]])]);
    $this->post(route('finance.invoices.adjust', $invoice), ['type'=>'refund','amount'=>'10000','reason'=>'Refund completed through Paystack.', 'external_reference'=>'501'])->assertSessionHasNoErrors();
    expect($invoice->fresh()->totals()['balance'])->toBe(1000000)->and($payment->fresh()->status)->toBe('success');
    $this->post(route('finance.invoices.adjust', $invoice), ['type'=>'refund','amount'=>'999999','reason'=>'Refund amount exceeds collected funds.', 'external_reference'=>'501'])->assertSessionHasErrors('external_reference');
    $this->post(route('finance.invoices.adjust', $invoice), ['type'=>'refund','amount'=>'10000','reason'=>'Duplicate external refund reference.', 'external_reference'=>'501'])->assertSessionHasNoErrors();
    expect($invoice->fresh()->totals()['balance'])->toBe(1000000);
});

it('applies exemptions to only the approved semester and supports revocation and expiry', function () {
    $invoice = tuitionFixture($this);
    $this->post(route('finance.sessions.enable', $this->session))->assertSessionHasNoErrors();
    $this->post(route('finance.invoices.exempt', $invoice), ['semester'=>'First', 'reason'=>'Approved temporary registration extension.', 'expires_on'=>today()->addDays(3)->format('Y-m-d')])->assertSessionHasNoErrors();
    $billing = app(TuitionBilling::class);
    expect($billing->clearance($this->student, $this->session->fresh(), 'First')['cleared'])->toBeTrue()
        ->and($billing->clearance($this->student, $this->session->fresh(), 'Second')['cleared'])->toBeFalse()
        ->and($invoice->fresh()->totals()['balance'])->toBe(20000000);
    $this->travel(4)->days();
    expect($billing->clearance($this->student, $this->session->fresh(), 'First')['cleared'])->toBeFalse();
    $this->travelBack();
    $this->post(route('finance.clearances.revoke', TuitionClearance::sole()), ['reason'=>'Extension revoked by administration.'])->assertSessionHasNoErrors();
    expect($billing->clearance($this->student, $this->session->fresh(), 'First')['cleared'])->toBeFalse();
});

it('gives accountants read access and reserves financial approvals for admins', function () {
    $invoice = tuitionFixture($this);
    foreach ([$this->bursar, $this->accountant] as $staff) {
        $this->actingAs($staff)->get(route('finance.invoices'))->assertOk()->assertSee($invoice->number);
        $this->get(route('finance.invoices.show', $invoice))->assertOk();
        $this->get(route('finance.schedules'))->assertOk();
        $this->get(route('finance.payments'))->assertOk();
        if ($staff->dashboardRole() === 'accountant') {
            $this->post(route('finance.invoices.adjust', $invoice), [])->assertForbidden();
        } else {
            $this->post(route('finance.invoices.adjust', $invoice), [])->assertSessionHasErrors('type');
        }
        $this->post(route('finance.invoices.exempt', $invoice), [])->assertForbidden();
        $this->post(route('finance.sessions.enable', $this->session))->assertForbidden();
    }
    $this->post(route('finance.schedules.store'), $this->scheduleData)->assertForbidden();
});

it('generates future-session invoices after reviewed activation and preserves previous bills', function () {
    $oldInvoice = tuitionFixture($this);
    $next = AcademicSession::create(['name'=>'2027/2028','start_year'=>2027,'end_year'=>2028]);
    $this->post(route('finance.schedules.store'), array_replace($this->scheduleData, ['academic_session_id'=>$next->id,'level'=>'100','category'=>'returning','due_date'=>'2027-11-01']))->assertSessionHasNoErrors();
    $this->post(route('finance.schedules.publish', TuitionSchedule::latest('id')->first()))->assertSessionHasNoErrors();
    expect(TuitionInvoice::count())->toBe(1);
    $this->post(route('admin.academic-sessions.activate', $next), ['confirmed'=>1, 'preview_token'=>app(\App\Services\Academic\SessionProgression::class)->preview($next)['token']])->assertSessionHasNoErrors();
    expect($this->student->fresh()->level)->toBe('100')->and(TuitionInvoice::count())->toBe(2)
        ->and($oldInvoice->fresh()->level)->toBe('100');
    $this->put(route('admin.academic-sessions.update', $this->session), ['name'=>'2028/2029'])->assertSessionHasErrors('name');
});

it('allows a new reference only after a verified failure and accounts for a late payment', function () {
    $invoice = tuitionFixture($this);
    $billing = app(TuitionBilling::class);
    $first = $billing->payment($invoice, 'full');
    $first->update(['status'=>'failed','verified_at'=>now()]);
    Http::fake(['api.paystack.co/transaction/verify/*'=>Http::response(['status'=>true,'data'=>[
        'id'=>100000 + $first->id,'reference'=>$first->reference,'status'=>'failed','amount'=>$first->amount,
        'currency'=>'NGN','domain'=>'test','customer'=>['email'=>$first->email],
    ]])]);
    $second = $billing->payment($invoice, 'full');
    expect($second->id)->not->toBe($first->id)->and($first->payable->fresh()->status)->toBe('superseded');
    verifyTuitionPayment($second);
    verifyTuitionPayment($first);
    expect($invoice->fresh()->totals()['overpayment'])->toBe(20000000)->and($invoice->fresh()->totals()['balance'])->toBe(0);
});

it('saves semester schedules without annual policy fields and ignores stale annual inputs', function ($period) {
    $data = array_replace($this->scheduleData, ['period'=>$period]);
    unset($data['first_percent']);
    $this->actingAs($this->admin)->post(route('finance.schedules.store'), $data)->assertSessionHasNoErrors();
    $schedule = TuitionSchedule::sole();
    expect($schedule->first_percent)->toBe(100)->and($schedule->second_due_date)->toBeNull();
    $this->put(route('finance.schedules.update', $schedule), $data + ['first_percent'=>'', 'second_due_date'=>'2020-01-01'])->assertSessionHasNoErrors();
    expect($schedule->fresh()->first_percent)->toBe(100)->and($schedule->fresh()->second_due_date)->toBeNull();
})->with(['First', 'Second']);

it('still requires a percentage for annual schedules', function () {
    $data = $this->scheduleData;
    unset($data['first_percent']);
    $this->actingAs($this->admin)->post(route('finance.schedules.store'), $data)->assertSessionHasErrors('first_percent');
    expect(TuitionSchedule::count())->toBe(0);
});

it('rejects invalid amounts and incomplete installment policies', function () {
    $this->actingAs($this->admin)->post(route('finance.schedules.store'), array_replace($this->scheduleData, ['amounts'=>['-1','10']]))->assertSessionHasErrors('amounts.0');
    $this->post(route('finance.schedules.store'), array_replace($this->scheduleData, ['first_percent'=>60]))->assertSessionHasErrors('second_due_date');
    expect(TuitionSchedule::count())->toBe(0);
});

it('does not guess a fee category when the entry year is missing', function () {
    $this->student->update(['entry_year' => null]);
    $this->actingAs($this->admin)->post(route('finance.schedules.store'), array_replace($this->scheduleData, ['category'=>'returning']))->assertSessionHasNoErrors();
    $this->post(route('finance.schedules.publish', TuitionSchedule::sole()))->assertSessionHasNoErrors();
    expect(TuitionInvoice::count())->toBe(0);
    $this->post(route('finance.sessions.enable', $this->session))->assertSessionHasErrors('session');
    $this->student->update(['entry_year'=>2025]);
    $this->post(route('finance.sessions.generate', $this->session))->assertSessionHasNoErrors();
    expect(TuitionInvoice::sole()->category)->toBe('returning');
});

it('reconciles unsettled tuition payments without initiating another charge', function () {
    $invoice = tuitionFixture($this);
    $payment = app(TuitionBilling::class)->payment($invoice, 'full');
    $payment->update(['created_at'=>now()->subMinutes(5)]);
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status'=>true, 'data'=>[
        'id'=>98765, 'reference'=>$payment->reference, 'amount'=>$payment->amount, 'currency'=>'NGN',
        'domain'=>'test', 'status'=>'success', 'customer'=>['email'=>$payment->email],
    ]])]);
    $this->artisan('payments:reconcile')->assertSuccessful();
    expect($payment->fresh()->status)->toBe('success')->and($invoice->fresh()->totals()['balance'])->toBe(0);
    Http::assertSent(fn ($request) => $request->method() === 'GET');
    Http::assertNotSent(fn ($request) => $request->method() === 'POST');
});

it('filters overdue invoices and preserves paid invoice totals', function () {
    $invoice = tuitionFixture($this, ['due_date'=>today()->subDay()->format('Y-m-d')]);
    $this->get(route('finance.invoices', ['status'=>'overdue']))->assertOk()->assertSee($invoice->number);
    verifyTuitionPayment(app(TuitionBilling::class)->payment($invoice, 'full'));
    $this->get(route('finance.invoices', ['status'=>'overdue']))->assertOk()->assertDontSee($invoice->number);
    $this->get(route('finance.invoices', ['status'=>'paid']))->assertOk()->assertSee($invoice->number)->assertSee('200,000.00');
});
