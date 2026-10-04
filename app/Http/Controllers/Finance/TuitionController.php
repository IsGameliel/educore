<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\{AcademicSession, Department, TuitionAdjustment, TuitionClearance, TuitionInvoice, TuitionSchedule, User};
use App\Services\TuitionBilling;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TuitionController extends Controller
{
    public function schedules(Request $request)
    {
        $editing = $request->filled('edit') ? TuitionSchedule::withCount('invoices')->findOrFail($request->integer('edit')) : null;
        if ($editing) {
            abort_unless($request->user()->dashboardRole() === 'admin'
                || ($request->user()->dashboardRole() === 'bursar' && $editing->status === 'draft'), 403);
        }
        $activeSession = AcademicSession::current();
        $missingStudents = collect();
        if ($activeSession) {
            $missingStudents = User::with('department')->where('usertype', 'student')->where(function ($query) use ($activeSession) {
                foreach (['First', 'Second'] as $semester) {
                    $query->orWhereNotIn('id', TuitionInvoice::active()->where('academic_session_id', $activeSession->id)->whereIn('period', ['Annual', $semester])->select('user_id'));
                }
            })->orderBy('name')->limit(50)->get();
        }
        return view('finance.schedules', [
            'schedules' => TuitionSchedule::with(['academicSession', 'department'])->latest()->paginate(20),
            'sessions' => AcademicSession::orderByDesc('start_year')->get(),
            'departments' => Department::orderBy('name')->get(), 'editing' => $editing,
            'activeSession' => $activeSession, 'missingStudents' => $missingStudents,
        ]);
    }

    public function save(Request $request, ?TuitionSchedule $schedule = null)
    {
        abort_unless(in_array($request->user()->dashboardRole(), ['admin', 'bursar'], true), 403);
        $data = $request->validate([
            'academic_session_id' => 'required|exists:academic_sessions,id', 'department_id' => 'required|exists:departments,id',
            'level' => 'required|in:100,200,300,400,500,600', 'category' => 'required|in:new,returning',
            'period' => 'required|in:Annual,First,Second', 'first_percent' => 'exclude_unless:period,Annual|required|integer|min:1|max:100',
            'due_date' => 'required|date_format:Y-m-d', 'second_due_date' => 'exclude_unless:period,Annual|exclude_if:first_percent,100|nullable|date_format:Y-m-d|after:due_date',
            'labels' => 'required|array|min:1|max:20', 'labels.*' => 'nullable|string|max:120',
            'amounts' => 'required|array|min:1|max:20', 'amounts.*' => ['nullable', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/'],
        ]);
        $items = [];
        if (array_keys($data['labels']) !== array_keys($data['amounts'])) {
            throw ValidationException::withMessages(['labels' => 'Each fee item must have a matching amount field.']);
        }
        foreach ($data['labels'] as $index => $label) {
            $value = $data['amounts'][$index] ?? null;
            if (blank($label) && blank($value)) { continue; }
            if (blank($label) || blank($value) || TuitionBilling::money($value) <= 0) {
                throw ValidationException::withMessages(['labels' => 'Each fee item requires a label and a positive amount.']);
            }
            $items[] = ['label' => trim($label), 'amount' => TuitionBilling::money($value)];
        }
        if (! $items) {
            throw ValidationException::withMessages(['labels' => 'Add at least one fee item.']);
        }
        if ($data['period'] === 'Annual' && $data['first_percent'] < 100 && empty($data['second_due_date'])) {
            throw ValidationException::withMessages(['second_due_date' => 'Installments require a second-semester balance deadline.']);
        }
        unset($data['labels'], $data['amounts']);
        if ($data['period'] !== 'Annual' || (int) $data['first_percent'] === 100) {
            $data['first_percent'] = 100;
            $data['second_due_date'] = null;
        }
        DB::transaction(function () use ($request, $schedule, $data, $items) {
            AcademicSession::whereIn('id', array_filter([$data['academic_session_id'], $schedule?->academic_session_id]))->orderBy('id')->lockForUpdate()->get();
            $before = null;
            if ($schedule) {
                $schedule = TuitionSchedule::lockForUpdate()->findOrFail($schedule->id);
                $before = $schedule->toArray();
                abort_unless($schedule->status === 'draft' || $request->user()->dashboardRole() === 'admin', 403);
                if ($schedule->invoices()->exists()) {
                    foreach (['academic_session_id', 'department_id', 'level', 'category', 'period'] as $field) {
                        if ((string) $schedule->{$field} !== (string) $data[$field]) {
                            throw ValidationException::withMessages([$field => 'This schedule has issued invoices. You can edit fees, deadlines and payment policy; create a separate schedule to change the enrollment or billing period.']);
                        }
                    }
                }
            }
            $duplicate = TuitionSchedule::where(collect($data)->only(['academic_session_id', 'department_id', 'level', 'category', 'period'])->all())
                ->when($schedule, fn ($q) => $q->whereKeyNot($schedule->id))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['period' => 'A fee schedule already exists for this cohort and billing period. Edit the existing schedule instead.']);
            }
            if ($schedule?->status === 'published') {
                $overlap = TuitionSchedule::where(collect($data)->only(['academic_session_id', 'department_id', 'level', 'category'])->all())
                    ->whereKeyNot($schedule->id)->where('status', 'published')
                    ->whereIn('period', $data['period'] === 'Annual' ? ['First', 'Second'] : ['Annual'])->exists();
                if ($overlap) {
                    throw ValidationException::withMessages(['period' => 'Use annual or semester billing for this cohort; they cannot overlap.']);
                }
            }
            $values = $data + ['items' => $items, 'amount' => array_sum(array_column($items, 'amount'))];
            $schedule ? $schedule->update($values) : $schedule = TuitionSchedule::create($values + ['created_by' => $request->user()->id]);
            ActivityLogger::log($request->user(), 'tuition_schedule_saved', 'Saved '.$schedule->status.' tuition schedule.', [
                'subject' => $schedule, 'properties' => ['before' => $before, 'after' => $schedule->fresh()->toArray()],
            ]);
        }, 3);
        return redirect()->route('finance.schedules')->with('success', $schedule?->status === 'published'
            ? 'Published schedule updated. Changes apply to future invoices; existing invoices and payments are unchanged.'
            : 'Draft fee schedule saved. Publish it when the amounts and deadlines are approved.');
    }

    public function destroy(Request $request, TuitionSchedule $schedule)
    {
        abort_unless($request->user()->dashboardRole() === 'admin', 403);
        DB::transaction(function () use ($request, $schedule) {
            AcademicSession::whereKey($schedule->academic_session_id)->lockForUpdate()->firstOrFail();
            $schedule = TuitionSchedule::lockForUpdate()->findOrFail($schedule->id);
            $before = $schedule->toArray();
            $schedule->update(['active_slot' => null]);
            $schedule->delete();
            ActivityLogger::log($request->user(), 'tuition_schedule_deleted', 'Deleted tuition schedule; issued invoices and payments retained.', [
                'subject' => $schedule, 'properties' => ['before' => $before],
            ]);
        }, 3);
        return redirect()->route('finance.schedules')->with('success', 'Schedule deleted and removed from student active tuition balances. Further checkout is disabled. Existing invoices and payment records are preserved for bursary review.');
    }

    public function publish(Request $request, TuitionSchedule $schedule, TuitionBilling $billing)
    {
        abort_unless($request->user()->dashboardRole() === 'admin', 403);
        DB::transaction(function () use ($request, $schedule, $billing) {
            $session = AcademicSession::lockForUpdate()->findOrFail($schedule->academic_session_id);
            $schedule = TuitionSchedule::lockForUpdate()->findOrFail($schedule->id);
            if ($schedule->academic_session_id !== $session->id) {
                throw ValidationException::withMessages(['schedule' => 'This schedule changed during publication. Reload and review it before publishing.']);
            }
            if ($schedule->status === 'published') { return; }
            $overlap = TuitionSchedule::where('academic_session_id', $session->id)->where('department_id', $schedule->department_id)
                ->where('level', $schedule->level)->where('category', $schedule->category)->where('status', 'published')
                ->where(fn ($q) => $schedule->period === 'Annual' ? $q->whereIn('period', ['First', 'Second']) : $q->where('period', 'Annual'))->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['schedule' => 'Use annual or semester billing for this cohort; they cannot overlap.']);
            }
            $schedule->update(['status' => 'published', 'published_by' => $request->user()->id, 'published_at' => now()]);
            ActivityLogger::log($request->user(), 'tuition_schedule_published', 'Published tuition fees.', ['subject' => $schedule]);
            $billing->generateSession($session);
        }, 3);
        return back()->with('success', 'Fees published. Matching students in the active session have been invoiced.');
    }

    public function enable(Request $request, AcademicSession $session, TuitionBilling $billing)
    {
        abort_unless($request->user()->dashboardRole() === 'admin', 403);
        DB::transaction(function () use ($request, $session, $billing) {
            $session = AcademicSession::lockForUpdate()->findOrFail($session->id);
            if (! TuitionSchedule::where('academic_session_id', $session->id)->where('status', 'published')->exists()) {
                throw ValidationException::withMessages(['session' => 'Publish fee schedules before enabling tuition clearance.']);
            }
            $billing->generateSession($session);
            if ($session->is_active) {
                foreach (['First', 'Second'] as $semester) {
                    $missing = User::where('usertype', 'student')->whereNotIn('id', TuitionInvoice::active()->where('academic_session_id', $session->id)->whereIn('period', ['Annual', $semester])->select('user_id'))->count();
                    if ($missing) {
                        throw ValidationException::withMessages(['session' => "{$missing} student(s) have no {$semester}-semester invoice. Complete their enrollment details and fee schedules first."]);
                    }
                }
            }
            $session->forceFill(['tuition_enabled' => true])->save();
            ActivityLogger::log($request->user(), 'tuition_clearance_enabled', 'Enabled tuition clearance for '.$session->name, ['subject' => $session]);
        }, 3);
        return back()->with('success', 'Tuition clearance is now required for course registration in this session.');
    }

    public function generate(Request $request, AcademicSession $session, TuitionBilling $billing)
    {
        abort_unless(in_array($request->user()->dashboardRole(), ['admin', 'bursar'], true), 403);
        abort_unless($session->is_active, 422, 'Activate the session before generating invoices.');
        $billing->generateSession($session);
        ActivityLogger::log($request->user(), 'tuition_invoices_generated', 'Generated missing tuition invoices.', ['subject' => $session]);
        return back()->with('success', 'Missing invoices generated from published schedules. Existing invoices were preserved.');
    }

    public function invoices(Request $request)
    {
        $filters = $request->validate([
            'session_id' => 'nullable|exists:academic_sessions,id', 'department_id' => 'nullable|exists:departments,id',
            'status' => 'nullable|in:paid,partially_paid,unpaid,overdue,credit,withdrawn,cancelled', 'search' => 'nullable|string|max:120',
        ]);
        $query = TuitionInvoice::with(['payments', 'adjustments', 'user', 'schedule'])
            ->when($filters['session_id'] ?? null, fn ($q, $v) => $q->where('academic_session_id', $v))
            ->when($filters['department_id'] ?? null, fn ($q, $v) => $q->where('department_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('number', 'like', "%{$v}%")->orWhere('student_name', 'like', "%{$v}%")
                ->orWhereHas('user', fn ($q) => $q->where('email', 'like', "%{$v}%")->orWhere('matric_number', 'like', "%{$v}%"))))
            ;
        $page = max(1, $request->integer('page', 1));
        $stats = ['expected'=>0, 'paid'=>0, 'balance'=>0, 'overpayment'=>0];
        $count = 0; $rows = collect();
        // Aggregate financial rules in bounded batches and retain only the requested page.
        foreach ($query->lazyByIdDesc(200) as $invoice) {
            $totals = $invoice->totals();
            $status = $filters['status'] ?? null;
            if ($status && ! match ($status) {
                'overdue'=>$invoice->overdue(), 'credit'=>$totals['overpayment'] > 0,
                'withdrawn'=>$invoice->needsWithdrawalReview(), default=>$totals['status'] === $status,
            }) { continue; }
            foreach (['expected'=>'due','paid'=>'paid','balance'=>'balance','overpayment'=>'overpayment'] as $key=>$field) { $stats[$key] += $totals[$field]; }
            if ($count >= ($page - 1) * 20 && $count < $page * 20) { $rows->push($invoice); }
            $count++;
        }
        $invoices = new LengthAwarePaginator($rows, $count, 20, $page, ['path' => $request->url(), 'query' => $request->query()]);
        return view('finance.invoices', compact('invoices', 'stats', 'filters') + [
            'sessions' => AcademicSession::orderByDesc('start_year')->get(), 'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function show(TuitionInvoice $invoice, TuitionBilling $billing)
    {
        $invoice->load(['payments', 'adjustments.recorder', 'user', 'academicSession']);
        $clearances = TuitionClearance::with('approver')->where('user_id', $invoice->user_id)->where('academic_session_id', $invoice->academic_session_id)->latest()->get();
        $registration = collect(['First', 'Second'])->mapWithKeys(fn ($semester) => [$semester => $billing->clearance($invoice->user, $invoice->academicSession, $semester)])->all();
        $replacements = TuitionSchedule::where($invoice->only(['academic_session_id','department_id','level','category','period']))->where('status','published')->get();
        return view('finance.invoice', compact('invoice', 'clearances', 'registration', 'replacements') + ['totals' => $invoice->totals()]);
    }

    public function resolveWithdrawal(Request $request, TuitionInvoice $invoice, \App\Services\WithdrawnTuition $withdrawals)
    {
        abort_unless($request->user()->dashboardRole() === 'admin', 403);
        $data = $request->validate(['action'=>'required|in:resume,cancel,replace,retain', 'reason'=>'required|string|min:10|max:2000',
            'schedule_id'=>'nullable|required_if:action,replace|integer|exists:tuition_schedules,id']);
        $result = $withdrawals->resolve($invoice, $request->user(), $data['action'], $data['reason'], $data['schedule_id'] ?? null);
        return redirect()->route('finance.invoices.show', $result->replacement_invoice_id ?? $result->id)->with('success', 'Withdrawal decision recorded. Original invoice and transaction history have been retained.');
    }

    public function reminders()
    {
        return view('finance.reminders', ['reminders'=>\App\Models\TuitionReminder::with('invoice.user')->latest()->paginate(30)]);
    }

    public function adjust(Request $request, TuitionInvoice $invoice)
    {
        abort_unless(in_array($request->user()->dashboardRole(), ['admin','bursar'], true), 403);
        $data = $request->validate(['type' => 'required|in:scholarship,waiver,refund',
            'amount' => ['required', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/'], 'reason' => 'required|string|min:10|max:2000',
            'external_reference' => 'nullable|required_if:type,refund|string|max:255',
            'legacy_adjustment_id' => 'nullable|integer']);
        $data['amount'] = TuitionBilling::money($data['amount']);
        $legacyId = $data['legacy_adjustment_id'] ?? null;
        unset($data['legacy_adjustment_id']);
        if ($data['type'] === 'refund') {
            abort_unless($request->user()->dashboardRole() === 'admin', 403);
            if ($legacyId && ! $invoice->adjustments()->whereKey($legacyId)->where('type', 'refund')->where('amount', $data['amount'])->exists()) {
                throw ValidationException::withMessages(['legacy_adjustment_id' => 'Choose a historical refund on this invoice with the same amount.']);
            }
            try {
                $record = app(\App\Services\PaymentFinancialSync::class)->refund($data['external_reference'], $invoice, $data['amount']);
            } catch (\Throwable $exception) {
                throw ValidationException::withMessages(['external_reference' => 'Enter the Paystack refund ID for a processed refund on this invoice, with its exact amount. Verification must succeed before recording it.']);
            }
            DB::transaction(function () use ($request, $invoice, $record, $data, $legacyId) {
                TuitionInvoice::lockForUpdate()->findOrFail($invoice->id);
                if ($legacyId) {
                    $legacy = $invoice->adjustments()->whereKey($legacyId)->where('type', 'refund')->lockForUpdate()->firstOrFail();
                    if ($legacy->amount !== $record->amount || ($legacy->gateway_change_id && $legacy->gateway_change_id !== $record->id)
                        || TuitionAdjustment::where('gateway_change_id', $record->id)->whereKeyNot($legacyId)->exists()) {
                        throw ValidationException::withMessages(['legacy_adjustment_id' => 'This refund is already linked or its amount does not match.']);
                    }
                    if (! $legacy->gateway_change_id) {
                        $legacy->update(['gateway_change_id' => $record->id]);
                        ActivityLogger::log($request->user(), 'tuition_refund_matched', $data['reason'], ['subject' => $legacy,
                            'target_user_id' => $invoice->user_id, 'properties' => ['gateway_change_id' => $record->id]]);
                    }
                    return;
                }
                $adjustment = TuitionAdjustment::firstOrCreate(['gateway_change_id' => $record->id],
                    $data + ['tuition_invoice_id' => $invoice->id, 'recorded_by' => $request->user()->id]);
                if ($adjustment->wasRecentlyCreated) {
                    ActivityLogger::log($request->user(), 'tuition_refund_verified', $data['reason'], ['subject' => $adjustment, 'target_user_id' => $invoice->user_id]);
                }
            }, 3);
            return back()->with('success', 'Paystack refund verified. The balance and clearance reflect this refund once.');
        }
        $data['external_reference'] = null;
        unset($data['external_reference']);
        app(\App\Services\FinancialApprovals::class)->request($invoice, $request->user(), $data);
        return back()->with('success', 'Adjustment requested. A different administrator must approve it before the balance changes.');
    }

    public function exempt(Request $request, TuitionInvoice $invoice)
    {
        abort_unless($request->user()->dashboardRole() === 'admin', 403);
        $data = $request->validate(['semester' => 'required|in:First,Second', 'reason' => 'required|string|min:10|max:2000', 'expires_on' => 'required|date_format:Y-m-d|after_or_equal:today']);
        DB::transaction(function () use ($request, $invoice, $data) {
            $clearance = TuitionClearance::create($data + ['user_id' => $invoice->user_id, 'academic_session_id' => $invoice->academic_session_id, 'approved_by' => $request->user()->id]);
            ActivityLogger::log($request->user(), 'tuition_exemption_approved', $data['reason'], ['subject' => $clearance, 'target_user_id' => $invoice->user_id]);
        });
        return back()->with('success', 'Temporary registration exemption approved. The tuition balance remains payable.');
    }

    public function revoke(Request $request, TuitionClearance $clearance)
    {
        abort_unless($request->user()->dashboardRole() === 'admin', 403);
        $data = $request->validate(['reason' => 'required|string|min:10|max:2000']);
        DB::transaction(function () use ($request, $clearance, $data) {
            $clearance = TuitionClearance::lockForUpdate()->findOrFail($clearance->id);
            if ($clearance->revoked_at) { return; }
            $clearance->update(['revoked_at' => now(), 'revoked_by' => $request->user()->id]);
            ActivityLogger::log($request->user(), 'tuition_exemption_revoked', $data['reason'], ['subject' => $clearance, 'target_user_id' => $clearance->user_id]);
        });
        return back()->with('success', 'Registration exemption revoked.');
    }
}
