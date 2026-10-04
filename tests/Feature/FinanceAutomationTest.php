<?php

use App\Imports\StudentImport;
use App\Jobs\SendTuitionReminder;
use App\Mail\TuitionPaymentReminder;
use App\Models\{AcademicSession, Department, Faculty, TuitionInvoice, TuitionReminder, TuitionSchedule, TuitionTemplate, User};
use App\Services\{TuitionBilling, TuitionReminders};
use Illuminate\Support\Facades\{DB, Http, Mail, Queue};

it('does not overwrite prior settlement evidence when transaction pagination fails', function () {
    config(['services.paystack.secret_key'=>'sk_test_controls']);
    \App\Models\PaymentSettlement::create(['gateway_id'=>'44','domain'=>'test','currency'=>'NGN','status'=>'success',
        'gross'=>10000,'fees'=>100,'net'=>9900,'matched_amount'=>10000,'unmatched_count'=>0,'exceptions'=>[], 'checked_at'=>now()->subDay()]);
    Http::fake(function ($request) {
        if (str_ends_with(parse_url($request->url(),PHP_URL_PATH),'/settlement')) {
            return Http::response(['status'=>true,'data'=>[['id'=>44,'domain'=>'test','currency'=>'NGN','status'=>'success',
                'total_processed'=>20000,'total_fees'=>200,'effective_amount'=>19800]],'meta'=>['pageCount'=>1]]);
        }
        if ((int) $request['page'] === 1) { return Http::response(['status'=>true,'data'=>[],'meta'=>['pageCount'=>2]]); }
        return Http::response([],503);
    });
    $this->artisan('payments:settlements')->assertFailed();
    expect((int) \App\Models\PaymentSettlement::sole()->gross)->toBe(10000);
    expect(DB::table('operation_health')->where('name','settlements')->value('status'))->toBe('failed');
});

it('recovers late success on a formerly failed checkout before issuing a second reference', function () {
    config(['services.paystack.secret_key'=>'sk_test_controls']);
    $invoice = financeInvoice($this);
    $payment = app(TuitionBilling::class)->payment($invoice,'full');
    $payment->update(['status'=>'failed','verified_at'=>now()->subHour()]);
    Http::fake(['api.paystack.co/transaction/verify/*'=>Http::response(['status'=>true,'data'=>[
        'id'=>7001,'reference'=>$payment->reference,'amount'=>$payment->amount,'status'=>'success',
        'currency'=>'NGN','domain'=>'test','customer'=>['email'=>$payment->email],'paid_at'=>now()->toIso8601String(),
    ]])]);
    $retry = app(TuitionBilling::class)->payment($invoice,'full');
    expect($retry->id)->toBe($payment->id)->and($retry->status)->toBe('success')->and(\App\Models\Payment::count())->toBe(1);
});

it('safely changes an uninitialized installment checkout to full payment but reuses initialized links', function () {
    $invoice = financeInvoice($this, ['period'=>'Annual','first_percent'=>60,'second_due_date'=>'2027-03-01']);
    $billing = app(TuitionBilling::class);
    $installment = $billing->payment($invoice, 'installment');
    $full = $billing->payment($invoice, 'full');
    expect($full->amount)->toBe(10000000)->and($installment->payable->fresh()->status)->toBe('superseded');
    $full->update(['initialization_attempted_at'=>now(),'authorization_url'=>'https://checkout.paystack.com/keep-link']);
    expect($billing->payment($invoice,'installment')->id)->toBe($full->id);
    Http::assertNothingSent();
});

it('surfaces late successful payments on replaced invoices for bursary review', function () {
    config(['services.paystack.secret_key'=>'sk_test_controls']);
    $invoice = financeInvoice($this);
    $payment = app(TuitionBilling::class)->payment($invoice,'full');
    withdrawFinanceInvoice($this,$invoice);
    $schedule = financeSchedule($this);
    $this->post(route('finance.invoices.withdrawal',$invoice), ['action'=>'replace','schedule_id'=>$schedule->id,'reason'=>'Replace unpaid invoice with approved new fees.'])->assertSessionHasNoErrors();
    Http::fake(['api.paystack.co/transaction/verify/*'=>Http::response(['status'=>true,'data'=>[
        'id'=>7001,'reference'=>$payment->reference,'amount'=>$payment->amount,'status'=>'success',
        'currency'=>'NGN','domain'=>'test','customer'=>['email'=>$payment->email],'paid_at'=>now()->toIso8601String(),
    ]])]);
    app(\App\Services\PaystackPayments::class)->verify($payment);
    expect($invoice->fresh()->totals()['overpayment'])->toBe(10000000)->and($invoice->fresh()->replacement->totals()['paid'])->toBe(0);
    $this->get(route('finance.controls'))->assertOk()->assertSee('Payments on cancelled invoices require bursary review')->assertSee($payment->reference);
});

it('validates tuition checkout callback duplicate webhook receipt and registration clearance end to end', function () {
    config(['services.paystack.secret_key'=>'sk_test_controls']);
    $invoice = financeInvoice($this);
    $this->session->forceFill(['tuition_enabled'=>true])->save();
    Http::fake(function ($request) {
        if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/initialize')) {
            return Http::response(['status'=>true,'data'=>['reference'=>$request['reference'],'authorization_url'=>'https://checkout.paystack.com/test-flow']]);
        }
        $payment = \App\Models\Payment::where('reference',basename(parse_url($request->url(), PHP_URL_PATH)))->firstOrFail();
        return Http::response(['status'=>true,'data'=>['id'=>7001,'reference'=>$payment->reference,
            'amount'=>$payment->amount,'currency'=>'NGN','domain'=>'test','status'=>'success',
            'customer'=>['email'=>$payment->email],'paid_at'=>now()->toIso8601String()]]);
    });
    $this->actingAs($this->student)->post(route('tuition.pay', $invoice), ['option'=>'full'])->assertRedirect('https://checkout.paystack.com/test-flow');
    $payment = \App\Models\Payment::sole();
    $this->get(route('payments.callback', ['reference'=>$payment->reference]))->assertRedirect(route('tuition.show',$invoice));
    $body = json_encode(['event'=>'charge.success','data'=>['reference'=>$payment->reference]]);
    foreach ([1,2] as $delivery) {
        $this->call('POST', route('payments.webhook'), [], [], [], ['CONTENT_TYPE'=>'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE'=>hash_hmac('sha512',$body,'sk_test_controls')],$body)->assertOk();
    }
    expect($invoice->fresh()->totals()['paid'])->toBe(10000000)->and($invoice->fresh()->totals()['balance'])->toBe(0);
    expect(app(TuitionBilling::class)->clearance($this->student,$this->session,'First')['cleared'])->toBeTrue();
    $this->get(route('payments.receipt',$payment))->assertOk()->assertHeader('content-type','application/pdf');
    Http::assertSentCount(2);
});

it('replaces an unpaid uninitialized checkout and disables its old link', function () {
    $invoice = financeInvoice($this);
    $payment = app(TuitionBilling::class)->payment($invoice, 'full');
    withdrawFinanceInvoice($this, $invoice);
    $schedule = financeSchedule($this);
    $this->post(route('finance.invoices.withdrawal', $invoice), ['action'=>'replace','schedule_id'=>$schedule->id,'reason'=>'Replace incorrect fees before checkout starts.'])->assertSessionHasNoErrors();
    expect($payment->payable->fresh()->status)->toBe('superseded');
    $this->actingAs($this->student)->post(route('payments.checkout', $payment))->assertSessionHasErrors('payment');
    Http::assertNothingSent();
});

it('requires fresh terminal gateway verification before replacing an initialized unpaid checkout', function (string $status, bool $allowed) {
    config(['services.paystack.secret_key'=>'sk_test_controls']);
    $invoice = financeInvoice($this);
    $payment = app(TuitionBilling::class)->payment($invoice, 'full');
    $payment->update(['initialization_attempted_at'=>now(), 'status'=>'failed', 'verified_at'=>now()->subDay()]);
    withdrawFinanceInvoice($this, $invoice);
    $schedule = financeSchedule($this);
    Http::fake(['api.paystack.co/transaction/verify/*'=>Http::response(['status'=>true,'data'=>[
        'id'=>7001, 'reference'=>$payment->reference, 'amount'=>$payment->amount, 'currency'=>'NGN',
        'customer'=>['email'=>$payment->email], 'domain'=>'test', 'status'=>$status,
    ]])]);
    $response = $this->post(route('finance.invoices.withdrawal', $invoice), ['action'=>'replace','schedule_id'=>$schedule->id,'reason'=>'Review all unpaid checkouts before replacing fees.']);
    if ($allowed) { $response->assertSessionHasNoErrors(); expect($invoice->fresh()->replacement_invoice_id)->not->toBeNull(); }
    else { $response->assertSessionHasErrors('action'); expect($invoice->fresh()->cancelled_at)->toBeNull(); }
})->with([['failed',true], ['abandoned',true], ['pending',false], ['success',false], ['reversed',false]]);

it('blocks replacement during gateway outages and preserves ambiguous initialization markers', function () {
    config(['services.paystack.secret_key'=>'sk_test_controls']);
    $invoice = financeInvoice($this);
    $payment = app(TuitionBilling::class)->payment($invoice, 'full');
    Http::fake(['api.paystack.co/*'=>Http::response([],503)]);
    try { app(\App\Services\PaystackPayments::class)->checkout($payment); } catch (\Throwable $exception) {}
    expect($payment->fresh()->initialization_attempted_at)->not->toBeNull();
    withdrawFinanceInvoice($this, $invoice);
    $this->post(route('finance.invoices.withdrawal', $invoice), ['action'=>'cancel','reason'=>'Gateway outage must prevent unsafe cancellation.'])->assertSessionHasErrors('action');
    expect($invoice->fresh()->cancelled_at)->toBeNull();
});

it('enforces independent approval rechecks current amounts and sends decision notifications', function () {
    \Illuminate\Support\Facades\Notification::fake();
    $invoice = financeInvoice($this);
    $this->actingAs($this->admin)->post(route('finance.invoices.adjust', $invoice), ['type'=>'waiver','amount'=>'60000','reason'=>'Fee waiver requiring independent review.'])->assertSessionHasNoErrors();
    $this->post(route('finance.invoices.adjust', $invoice), ['type'=>'waiver','amount'=>'60000','reason'=>'Fee waiver requiring independent review.'])->assertSessionHasNoErrors();
    $approval = \App\Models\FinancialApproval::sole();
    expect($invoice->fresh()->totals()['credits'])->toBe(0);
    $decision = ['decision'=>'approved','reason'=>'Verified independently with supporting documentation.'];
    $this->post(route('finance.approvals.review', $approval), $decision)->assertForbidden();
    $this->actingAs($this->accountant)->post(route('finance.approvals.review', $approval), $decision)->assertForbidden();
    $reviewer = User::factory()->create(['usertype'=>'admin']);
    $this->actingAs($reviewer)->post(route('finance.approvals.review', $approval), $decision)->assertSessionHasNoErrors();
    $this->post(route('finance.approvals.review', $approval), $decision)->assertSessionHasErrors('approval');
    expect($invoice->fresh()->totals()['credits'])->toBe(6000000);
    \Illuminate\Support\Facades\Notification::assertSentTo($this->student, \App\Notifications\FinancialApprovalUpdate::class);
    $this->actingAs($this->bursar)->post(route('finance.invoices.adjust', $invoice), ['type'=>'waiver','amount'=>'40000','reason'=>'Remaining fees considered for waiver.'])->assertSessionHasNoErrors();
    $next = \App\Models\FinancialApproval::latest('id')->first();
    $invoice->adjustments()->create(['type'=>'waiver','amount'=>1000000,'reason'=>'Earlier approved waiver.', 'recorded_by'=>$reviewer->id]);
    $this->actingAs($reviewer)->post(route('finance.approvals.review', $next), $decision)->assertSessionHasErrors('amount');
    $this->get(route('finance.controls'))->assertOk()->assertSee('Financial approvals');
});

it('reconciles paginated settlements idempotently and reports foreign or mismatched transactions', function () {
    config(['services.paystack.secret_key'=>'sk_test_controls']);
    $invoice = financeInvoice($this);
    $payment = app(TuitionBilling::class)->payment($invoice, 'full');
    $payment->update(['status'=>'success','gateway_id'=>'7001']);
    Http::fake(function ($request) use ($payment) {
        if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/settlement')) {
            return Http::response(['status'=>true, 'data'=>[['id'=>44,'domain'=>'test','currency'=>'NGN','status'=>'success',
                'total_processed'=>10000100,'total_fees'=>200100,'effective_amount'=>9800000,'settlement_date'=>'2026-10-02']], 'meta'=>['pageCount'=>1]]);
        }
        $known = ['id'=>7001,'reference'=>$payment->reference,'amount'=>$payment->amount,'fees'=>200000,'currency'=>'NGN','domain'=>'test','status'=>'success'];
        $unknown = ['id'=>7002,'reference'=>'foreign-reference','amount'=>100,'fees'=>100,'currency'=>'NGN','domain'=>'test','status'=>'success'];
        return Http::response(['status'=>true,'data'=>[(int) $request['page'] === 1 ? $known : $unknown],'meta'=>['pageCount'=>2]]);
    });
    $this->artisan('payments:settlements')->assertSuccessful();
    $this->artisan('payments:settlements')->assertSuccessful();
    $settlement = \App\Models\PaymentSettlement::sole();
    expect((int) $settlement->matched_amount)->toBe(10000000)->and($settlement->exceptions)->toBe(['foreign-reference']);
    expect($invoice->fresh()->totals()['paid'])->toBe(10000000);
    $this->actingAs($this->accountant)->get(route('finance.controls'))->assertOk()->assertSee('foreign-reference');
    $this->actingAs($this->student)->get(route('finance.controls'))->assertForbidden();
});

beforeEach(function () {
    Mail::fake(); Queue::fake(); Http::preventStrayRequests();
    config(['tuition.email_reminders'=>true]);
    $this->travelTo(\Carbon\Carbon::parse('2026-10-02 09:00:00'));
    $faculty = Faculty::create(['name'=>'Science','code'=>'SCI']);
    $this->department = Department::create(['name'=>'Computing','faculty_id'=>$faculty->id]);
    $this->session = AcademicSession::create(['name'=>'2026/2027','start_year'=>2026,'end_year'=>2027,'is_active'=>true]);
    $this->admin = User::factory()->create(['usertype'=>'admin']);
    $this->bursar = User::factory()->create(['usertype'=>'bursar']);
    $this->accountant = User::factory()->create(['usertype'=>'accountant']);
    $this->student = User::factory()->create(['usertype'=>'student','department_id'=>$this->department->id,'level'=>'100','entry_year'=>2026]);
});

function financeSchedule($test, array $overrides = []): TuitionSchedule
{
    return TuitionSchedule::create(array_replace(['academic_session_id'=>$test->session->id,'department_id'=>$test->department->id,
        'level'=>'100','category'=>'new','period'=>'First','items'=>[['label'=>'Tuition','amount'=>10000000]],'amount'=>10000000,
        'first_percent'=>100,'due_date'=>'2026-10-09','status'=>'published','created_by'=>$test->admin->id], $overrides));
}

function financeInvoice($test, array $overrides = []): TuitionInvoice
{
    financeSchedule($test, $overrides);
    app(TuitionBilling::class)->ensureInvoices($test->student, $test->session);
    return TuitionInvoice::current()->where('user_id',$test->student->id)->sole();
}

function withdrawFinanceInvoice($test, TuitionInvoice $invoice): void
{
    $test->actingAs($test->admin)->delete(route('finance.schedules.destroy', $invoice->tuition_schedule_id))->assertSessionHasNoErrors();
}

it('saves immutable templates and applies them to multiple cohorts as drafts without billing', function () {
    $source = financeSchedule($this);
    $this->actingAs($this->bursar)->post(route('finance.templates.store'), ['name'=>'First semester science','schedule_id'=>$source->id])->assertSessionHasNoErrors();
    $template = TuitionTemplate::sole();
    $source->update(['amount'=>20000000,'items'=>[['label'=>'Changed tuition','amount'=>20000000]]]);
    expect($template->fresh()->amount)->toBe(10000000);
    $target = AcademicSession::create(['name'=>'2027/2028','start_year'=>2027,'end_year'=>2028]);
    $data = ['academic_session_id'=>$target->id,'department_ids'=>[$this->department->id],'levels'=>['100','200'],
        'category'=>'returning','due_date'=>'2027-11-01'];
    $this->post(route('finance.templates.apply', $template), $data)->assertSessionHasNoErrors();
    $this->post(route('finance.templates.apply', $template), $data)->assertSessionHasNoErrors();
    expect(TuitionSchedule::where('academic_session_id',$target->id)->count())->toBe(2)
        ->and(TuitionSchedule::where('academic_session_id',$target->id)->where('status','draft')->count())->toBe(2)
        ->and(TuitionInvoice::count())->toBe(0);
    $this->get(route('finance.templates'))->assertOk()->assertSee('First semester science');
    $this->delete(route('finance.templates.destroy', $template))->assertSessionHasNoErrors();
    expect(TuitionSchedule::count())->toBe(3);
});

it('requires new installment deadlines for annual templates and restricts finance mutations', function () {
    $source = financeSchedule($this, ['period'=>'Annual','first_percent'=>60,'second_due_date'=>'2027-03-01']);
    $this->actingAs($this->admin)->post(route('finance.templates.store'), ['name'=>'Annual template','schedule_id'=>$source->id])->assertSessionHasNoErrors();
    $template = TuitionTemplate::sole();
    $data = ['academic_session_id'=>$this->session->id,'department_ids'=>[$this->department->id],'levels'=>['200'],'category'=>'returning','due_date'=>'2026-11-01'];
    $this->post(route('finance.templates.apply', $template), $data)->assertSessionHasErrors('second_due_date');
    foreach ([$this->student, $this->accountant] as $user) {
        $this->actingAs($user)->post(route('finance.templates.apply', $template), $data)->assertForbidden();
        $this->delete(route('finance.templates.destroy', $template))->assertForbidden();
    }
    $this->actingAs($this->student)->get(route('finance.reminders'))->assertForbidden();
});

it('generates invoices after student creation enrollment correction and import', function () {
    financeSchedule($this);
    $new = User::factory()->create(['usertype'=>'student','department_id'=>$this->department->id,'level'=>'100','entry_year'=>2026]);
    expect(TuitionInvoice::where('user_id',$new->id)->count())->toBe(1);
    $incomplete = User::factory()->create(['usertype'=>'student','department_id'=>$this->department->id,'level'=>'100','entry_year'=>null]);
    expect(TuitionInvoice::where('user_id',$incomplete->id)->count())->toBe(0);
    $incomplete->update(['entry_year'=>2026]);
    expect(TuitionInvoice::where('user_id',$incomplete->id)->count())->toBe(1);
    $import = new StudentImport;
    $import->collection(collect([['name'=>'Imported Student','email'=>'finance-import@example.com','department_id'=>$this->department->id,'level'=>'100','entry_year'=>2026]]));
    $imported = User::where('email','finance-import@example.com')->sole();
    expect(TuitionInvoice::where('user_id',$imported->id)->count())->toBe(1);
});

it('does not bill rolled back enrollment and catches bulk changes in the scheduled fallback', function () {
    financeSchedule($this);
    $id = null;
    try {
        DB::transaction(function () use (&$id) {
            $id = User::factory()->create(['usertype'=>'student','department_id'=>$this->department->id,'level'=>'100','entry_year'=>2026])->id;
            throw new RuntimeException('Rollback test');
        });
    } catch (RuntimeException $exception) {}
    expect(TuitionInvoice::where('user_id',$id)->count())->toBe(0);
    $this->artisan('tuition:generate')->assertSuccessful();
    $this->artisan('tuition:generate')->assertSuccessful();
    expect(TuitionInvoice::where('user_id',$this->student->id)->count())->toBe(1);
    $invoice = TuitionInvoice::sole();
    $this->student->update(['level'=>'200']);
    expect($invoice->fresh()->level)->toBe('100')->and(TuitionInvoice::count())->toBe(1);
});

it('queues each reminder window once and prevents duplicate job delivery', function () {
    $invoice = financeInvoice($this);
    $reminders = app(TuitionReminders::class);
    expect($reminders->queue($invoice))->toBeTrue()->and($reminders->queue($invoice))->toBeFalse();
    Queue::assertPushed(SendTuitionReminder::class, 1);
    $job = new SendTuitionReminder(TuitionReminder::sole()->id);
    $job->handle(); $job->handle();
    Mail::assertSent(TuitionPaymentReminder::class, 1);
    Mail::assertSent(TuitionPaymentReminder::class, fn ($mail) => $mail->hasTo($this->student->email) && $mail->notice['amount'] === 10000000);
    expect(TuitionReminder::sole()->status)->toBe('sent');
    $this->actingAs($this->student)->get(route('dashboard'))->assertOk()->assertSee('Tuition payment due soon');
    $this->get(route('tuition.index'))->assertOk()->assertSee('Tuition payment due soon');
});

it('uses due-day and weekly overdue windows without daily duplicate reminders', function () {
    $invoice = financeInvoice($this, ['due_date'=>today()->format('Y-m-d')]);
    $service = app(TuitionReminders::class);
    expect(TuitionReminders::notice($invoice)['kind'])->toBe('due_today');
    $service->queue($invoice); (new SendTuitionReminder(TuitionReminder::latest('id')->first()->id))->handle();
    $this->travel(1)->days();
    expect($service->queue($invoice))->toBeTrue();
    (new SendTuitionReminder(TuitionReminder::latest('id')->first()->id))->handle();
    $this->travel(5)->days(); expect($service->queue($invoice))->toBeFalse();
    $this->travel(2)->days(); expect($service->queue($invoice))->toBeTrue();
    expect(TuitionReminder::count())->toBe(3);
});

it('rechecks payments and withdrawal before sending queued reminders', function ($change) {
    $invoice = financeInvoice($this);
    app(TuitionReminders::class)->queue($invoice);
    if ($change === 'paid') {
        app(TuitionBilling::class)->payment($invoice, 'full')->update(['status'=>'success']);
    } else { withdrawFinanceInvoice($this, $invoice); }
    (new SendTuitionReminder(TuitionReminder::sole()->id))->handle();
    Mail::assertNothingSent();
    expect(TuitionReminder::sole()->status)->toBe('skipped');
})->with(['paid','withdrawn']);

it('reminds only the unpaid installment and respects fee adjustments', function () {
    $invoice = financeInvoice($this, ['period'=>'Annual','first_percent'=>60,'due_date'=>'2026-10-02','second_due_date'=>'2027-03-01']);
    expect(TuitionReminders::notice($invoice)['amount'])->toBe(6000000);
    $payment = app(TuitionBilling::class)->payment($invoice,'installment'); $payment->update(['status'=>'success']);
    expect(TuitionReminders::notice($invoice->fresh()))->toBeNull();
    $this->travelTo(\Carbon\Carbon::parse('2027-02-25'));
    $invoice->adjustments()->create(['type'=>'waiver','amount'=>1000000,'reason'=>'Approved waiver for this student.','recorded_by'=>$this->admin->id]);
    expect(TuitionReminders::notice($invoice->fresh())['amount'])->toBe(3000000);
});

it('supports dry-run reminder checks without queuing or sending messages', function () {
    financeInvoice($this);
    $this->artisan('tuition:remind', ['--dry-run'=>true])->expectsOutput('Eligible invoices: 1')->assertSuccessful();
    Queue::assertNothingPushed(); Mail::assertNothingSent(); expect(TuitionReminder::count())->toBe(0);
    $this->artisan('tuition:remind')->assertSuccessful();
    expect(TuitionReminder::count())->toBe(1);
});

it('replaces an untouched withdrawn invoice once and retains the old bill as cancelled history', function () {
    $invoice = financeInvoice($this);
    withdrawFinanceInvoice($this, $invoice);
    $replacement = financeSchedule($this, ['amount'=>12000000,'items'=>[['label'=>'New tuition','amount'=>12000000]]]);
    $this->get(route('finance.invoices', ['status'=>'withdrawn']))->assertOk()->assertSee($invoice->number);
    $this->get(route('finance.invoices.show', $invoice))->assertOk()->assertSee('Resolve withdrawn invoice')->assertSee('120,000.00');
    $data = ['action'=>'replace','schedule_id'=>$replacement->id,'reason'=>'Approved replacement of withdrawn fee schedule.'];
    $this->post(route('finance.invoices.withdrawal', $invoice), $data)->assertSessionHasNoErrors();
    $new = $invoice->fresh()->replacement;
    expect($new->amount)->toBe(12000000)->and($invoice->fresh()->totals()['due'])->toBe(0)->and($invoice->fresh()->amount)->toBe(10000000);
    $this->post(route('finance.invoices.withdrawal', $invoice), $data)->assertSessionHasErrors('action');
    $this->artisan('tuition:generate')->assertSuccessful();
    expect(TuitionInvoice::count())->toBe(2)->and(TuitionInvoice::active()->count())->toBe(1);
    $this->actingAs($this->student)->get(route('tuition.index'))->assertOk()->assertSee($new->number)->assertDontSee($invoice->number);
    $this->get(route('tuition.show', $invoice))->assertOk()->assertSee('View its replacement invoice');
});

it('holds cancelled billing until an explicit replacement even if the student visits again', function () {
    $invoice = financeInvoice($this); withdrawFinanceInvoice($this, $invoice);
    $replacement = financeSchedule($this);
    $this->post(route('finance.invoices.withdrawal', $invoice), ['action'=>'cancel','reason'=>'Cancel the incorrectly issued fee invoice.'])->assertSessionHasNoErrors();
    $this->artisan('tuition:generate')->assertSuccessful();
    $this->actingAs($this->student)->get(route('tuition.index'))->assertOk()->assertDontSee($invoice->number);
    expect(TuitionInvoice::count())->toBe(1)->and(TuitionInvoice::active()->count())->toBe(0);
    $this->actingAs($this->admin)->post(route('finance.invoices.withdrawal', $invoice), ['action'=>'replace','schedule_id'=>$replacement->id,'reason'=>'Approved reissue after resolving the billing hold.'])->assertSessionHasNoErrors();
    expect(TuitionInvoice::active()->count())->toBe(1);
});

it('blocks cancellation with payment attempts and resumes the same invoice without losing money', function () {
    $invoice = financeInvoice($this, ['period'=>'Annual','first_percent'=>60,'second_due_date'=>'2027-03-01']);
    $payment = app(TuitionBilling::class)->payment($invoice,'installment'); $payment->update(['status'=>'success']);
    withdrawFinanceInvoice($this, $invoice);
    $this->post(route('finance.invoices.withdrawal', $invoice), ['action'=>'cancel','reason'=>'Attempt to cancel a partly paid invoice.'])->assertSessionHasErrors('action');
    $this->post(route('finance.invoices.withdrawal', $invoice), ['action'=>'resume','reason'=>'Continue collecting the original approved fees.'])->assertSessionHasNoErrors();
    expect($invoice->fresh()->isActive())->toBeTrue()->and($invoice->fresh()->totals()['paid'])->toBe(6000000)->and($invoice->fresh()->totals()['balance'])->toBe(4000000);
    expect(TuitionInvoice::active()->count())->toBe(1);
});

it('reserves withdrawal decisions for admins and rejects mismatched replacement cohorts', function () {
    $invoice = financeInvoice($this); withdrawFinanceInvoice($this, $invoice);
    $schedule = financeSchedule($this, ['level'=>'200']);
    foreach ([$this->bursar,$this->accountant,$this->student] as $user) {
        $this->actingAs($user)->post(route('finance.invoices.withdrawal', $invoice), ['action'=>'resume','reason'=>'Resume approved original tuition invoice.'])->assertForbidden();
    }
    $this->actingAs($this->admin)->post(route('finance.invoices.withdrawal', $invoice), ['action'=>'replace','schedule_id'=>$schedule->id,'reason'=>'This replacement has the wrong cohort.'])->assertSessionHasErrors('schedule_id');
    expect($invoice->fresh()->cancelled_at)->toBeNull();
});

it('renders the reminder email with the correct amount and invoice link', function () {
    $invoice = financeInvoice($this);
    $url = route('tuition.show', $invoice);
    $html = (new TuitionPaymentReminder($invoice->number, $this->student->name, TuitionReminders::notice($invoice), $url))->render();
    expect($html)->toContain('100,000.00', $invoice->number, $url, '2026-10-09');
});

it('retains settled withdrawn history and reopens review when a recorded refund creates a balance', function () {
    $invoice = financeInvoice($this);
    $payment = app(TuitionBilling::class)->payment($invoice, 'full');
    $payment->update(['status'=>'success']);
    withdrawFinanceInvoice($this, $invoice);
    $this->post(route('finance.invoices.withdrawal', $invoice), ['action'=>'retain','reason'=>'Retain this fully paid invoice for historical records.'])->assertSessionHasNoErrors();
    expect($invoice->fresh()->needsWithdrawalReview())->toBeFalse();
    $invoice->adjustments()->create(['type'=>'refund','amount'=>1000000,'reason'=>'Completed partial gateway refund.','external_reference'=>'refund-review-test','recorded_by'=>$this->admin->id]);
    expect($invoice->fresh()->needsWithdrawalReview())->toBeTrue();
    $this->get(route('finance.invoices', ['status'=>'withdrawn']))->assertOk()->assertSee($invoice->number);
});
