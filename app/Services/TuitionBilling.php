<?php

namespace App\Services;

use App\Models\{AcademicSession, Payment, TuitionCharge, TuitionClearance, TuitionInvoice, TuitionSchedule, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TuitionBilling
{
    public static function money(string $value): int
    {
        if (! preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $value)) {
            throw ValidationException::withMessages(['amount' => 'Enter a valid naira amount with at most two decimal places.']);
        }
        [$whole, $fraction] = array_pad(explode('.', $value), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public function ensureInvoices(User $student, AcademicSession $session): void
    {
        if ($student->dashboardRole() !== 'student' || ! $session->is_active) {
            return;
        }
        DB::transaction(function () use ($student, $session) {
            $session = AcademicSession::lockForUpdate()->findOrFail($session->id);
            if (! $session->is_active) { return; }
            $student = User::lockForUpdate()->findOrFail($student->id);
            if ($student->dashboardRole() !== 'student') { return; }
            // An existing invoice is the enrollment snapshot for the whole session.
            $snapshot = TuitionInvoice::current()->where('user_id', $student->id)->where('academic_session_id', $session->id)->first();
            if (! $snapshot && (! $student->entry_year || (int) $student->entry_year > (int) $session->start_year)) {
                return; // Do not guess a fee category from incomplete or future enrollment data.
            }
            $category = $snapshot?->category ?? ((int) $student->entry_year === (int) $session->start_year ? 'new' : 'returning');
            $schedules = TuitionSchedule::where('academic_session_id', $session->id)->where('status', 'published')
                ->where('department_id', $snapshot?->department_id ?? $student->department_id)
                ->where('level', $snapshot?->level ?? $student->level)->where('category', $category)->with('department')->lockForUpdate()->get();
            foreach ($schedules as $schedule) {
                // A cancellation is an explicit billing hold until an admin replaces it.
                $heldPeriods = $schedule->period === 'Annual' ? ['Annual','First','Second'] : ['Annual', $schedule->period];
                if (TuitionInvoice::where('user_id', $student->id)->where('academic_session_id', $session->id)
                    ->whereNotNull('cancelled_at')->whereNull('replacement_invoice_id')->whereIn('period', $heldPeriods)->exists()) { continue; }
                // A replacement schedule must not add annual tuition on top of existing semester bills, or vice versa.
                $overlap = TuitionInvoice::current()->where('user_id', $student->id)->where('academic_session_id', $session->id)
                    ->whereIn('period', $schedule->period === 'Annual' ? ['First', 'Second'] : ['Annual'])->exists();
                if ($overlap) { continue; }
                $this->issue($student, $schedule);
            }
        }, 3);
    }

    public function issue(User $student, TuitionSchedule $schedule): TuitionInvoice
    {
        return TuitionInvoice::current()->firstOrCreate([
            'user_id'=>$student->id, 'academic_session_id'=>$schedule->academic_session_id, 'period'=>$schedule->period, 'active_slot'=>1,
        ], [
            'number'=>'TU-'.Str::upper((string) Str::ulid()), 'tuition_schedule_id'=>$schedule->id,
            'student_name'=>$student->name, 'department_id'=>$schedule->department_id, 'department_name'=>$schedule->department->name,
            'session_name'=>$schedule->academicSession->name, 'level'=>$schedule->level, 'category'=>$schedule->category,
            'items'=>$schedule->items, 'amount'=>$schedule->amount, 'first_percent'=>$schedule->first_percent,
            'due_date'=>$schedule->due_date, 'second_due_date'=>$schedule->second_due_date,
        ]);
    }

    public function generateSession(AcademicSession $session): void
    {
        if (! $session->is_active) {
            return;
        }
        User::where('usertype', 'student')->orderBy('id')->chunkById(100, function ($students) use ($session) {
            foreach ($students as $student) {
                $this->ensureInvoices($student, $session);
            }
        });
    }

    public function clearance(User $student, AcademicSession $session, string $semester, bool $enforce = false): array
    {
        if (! $enforce && ! $session->tuition_enabled) {
            return ['cleared' => true, 'message' => 'Tuition clearance is not enabled for this session.'];
        }
        $exemption = TuitionClearance::where('user_id', $student->id)->where('academic_session_id', $session->id)
            ->where('semester', $semester)->whereNull('revoked_at')->whereDate('expires_on', '>=', today())->exists();
        if ($exemption) {
            return ['cleared' => true, 'message' => 'An approved registration exemption applies.'];
        }
        $invoice = TuitionInvoice::current()->with(['payments', 'adjustments'])->where('user_id', $student->id)
            ->where('academic_session_id', $session->id)->whereIn('period', ['Annual', $semester])->first();
        if (! $invoice) {
            return ['cleared' => false, 'message' => 'Tuition has not been configured for your enrollment. Please contact the bursary.'];
        }
        if ($invoice->totals()['reversal_review']) {
            return ['cleared' => false, 'message' => 'Paystack reports a reversal whose amount is still being verified. Contact the bursary before paying again.'];
        }
        if ($invoice->totals()['refund_review']) {
            return ['cleared' => false, 'message' => 'The bursary must match a historical refund to its Paystack record before confirming your balance and clearance.'];
        }
        if ($invoice->totals()['dispute_open']) {
            return ['cleared' => false, 'message' => 'A tuition payment dispute is under review. Contact the bursary; do not pay the disputed amount again while it is being reviewed.'];
        }
        $remaining = max(0, $invoice->requiredAmount($semester) - $invoice->totals()['paid']);
        if ($remaining > 0 && ! $invoice->isActive()) {
            return ['cleared' => false, 'message' => 'Your tuition schedule has been withdrawn. Contact the bursary about registration clearance.'];
        }

        return ['cleared' => $remaining === 0, 'message' => $remaining === 0 ? 'Cleared for course registration.'
            : 'Pay ₦'.number_format($remaining / 100, 2).' toward tuition to register for '.$semester.' semester ('.$session->name.').'];
    }

    public function assertCleared(User $student, string $sessionName, string $semester): void
    {
        $session = AcademicSession::where('name', $sessionName)->first();
        if (! $session || ! $session->tuition_enabled) {
            return;
        }
        $this->ensureInvoices($student, $session);
        $clearance = $this->clearance($student, $session, $semester);
        if (! $clearance['cleared']) {
            throw ValidationException::withMessages(['tuition' => $clearance['message']]);
        }
    }

    public function payment(TuitionInvoice $invoice, string $option): Payment
    {
        return DB::transaction(function () use ($invoice, $option) {
            $invoice = TuitionInvoice::lockForUpdate()->findOrFail($invoice->id);
            if (! $invoice->isActive()) {
                throw ValidationException::withMessages(['tuition' => 'This tuition schedule has been withdrawn. Further payments are disabled.']);
            }
            if ($invoice->totals()['refund_review'] || $invoice->totals()['reversal_review']) {
                throw ValidationException::withMessages(['payment' => 'The bursary must reconcile historical refund records before another payment.']);
            }
            $balance = $invoice->totals()['balance'];
            if ($balance === 0) {
                throw ValidationException::withMessages(['payment' => 'This invoice has no outstanding balance.']);
            }
            $pending = $invoice->charges()->where('status', 'awaiting_payment')->with('payment')->first();
            if ($pending) {
                // Recheck terminal evidence; a previous failure may have settled since.
                if (in_array($pending->payment?->status, ['failed', 'abandoned', 'reversed'], true) && $pending->payment->verified_at) {
                    try { $verified = app(PaystackPayments::class)->verify($pending->payment, true); }
                    catch (\Throwable $exception) { throw ValidationException::withMessages(['payment'=>'Paystack must confirm the previous attempt before a replacement checkout can be created.']); }
                    if (! in_array($verified->status, ['failed','abandoned','reversed'], true)) { return $verified; }
                    $pending->update(['status' => 'superseded']);
                    $pending = null;
                }
            }
            $amount = $balance;
            if ($option === 'installment' && $invoice->period === 'Annual' && $invoice->first_percent < 100) {
                $firstRemaining = max(0, $invoice->requiredAmount('First') - $invoice->totals()['paid']);
                $amount = $firstRemaining > 0 ? $firstRemaining : $balance;
            }
            if ($pending) {
                $payment = $pending->payment;
                if ($payment && $payment->status === 'pending' && ! $payment->initialization_attempted_at
                    && ! $payment->authorization_url && ! $payment->verified_at && $pending->amount !== $amount) {
                    $pending->update(['status'=>'superseded']);
                } else { return $payment; }
            }
            $charge = $invoice->charges()->create(['amount' => $amount]);
            return app(PaystackPayments::class)->create($charge, $invoice->user, 'tuition');
        }, 3);
    }
}
