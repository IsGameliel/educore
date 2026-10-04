<?php
namespace App\Services;

use App\Models\{AcademicSession, TuitionInvoice, TuitionSchedule, User};
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawnTuition
{
    public function resolve(TuitionInvoice $invoice, User $actor, string $action, string $reason, ?int $scheduleId = null): TuitionInvoice
    {
        return DB::transaction(function () use ($invoice, $actor, $action, $reason, $scheduleId) {
            AcademicSession::whereKey($invoice->academic_session_id)->lockForUpdate()->firstOrFail();
            User::whereKey($invoice->user_id)->lockForUpdate()->firstOrFail();
            $invoice = TuitionInvoice::lockForUpdate()->findOrFail($invoice->id);
            if ($invoice->isActive() || $invoice->replacement_invoice_id || ($invoice->cancelled_at && $action !== 'replace')) {
                throw ValidationException::withMessages(['action'=>'This invoice no longer needs this withdrawal action. Refresh and review it.']);
            }
            $before = $invoice->toArray();
            $review = ['reviewed_by'=>$actor->id,'review_reason'=>$reason,'withdrawal_reviewed_at'=>now()];
            if ($action === 'resume') {
                $invoice->update($review + ['resumed_at'=>now()]);
            } elseif ($action === 'retain') {
                if ($invoice->totals()['balance'] > 0) {
                    throw ValidationException::withMessages(['action'=>'An outstanding balance must be resumed or resolved before closing review.']);
                }
                $invoice->update($review);
            } else {
                $payments = $invoice->payments()->lockForUpdate()->get();
                foreach ($payments as $payment) {
                    if ($payment->initialization_attempted_at || $payment->authorization_url || $payment->verified_at || $payment->status !== 'pending') {
                        try { $payment = app(PaystackPayments::class)->verify($payment, true); }
                        catch (\Throwable $exception) {
                            throw ValidationException::withMessages(['action'=>'Paystack could not confirm this checkout. Replacement is blocked until its status can be verified.']);
                        }
                        if (! in_array($payment->status, ['failed', 'abandoned'], true) || $payment->gateway_reversed) {
                            throw ValidationException::withMessages(['action'=>'A payment is settled or still payable. Resume the original invoice and reconcile it before replacement.']);
                        }
                    }
                }
                if ($invoice->adjustments()->exists() || $invoice->charges()->where('status', 'paid')->exists()) {
                    throw ValidationException::withMessages(['action'=>'This invoice has payment attempts or adjustments. Resume the original invoice and review its transactions; it cannot be cancelled or replaced automatically.']);
                }
                $invoice->charges()->where('status', 'awaiting_payment')->update(['status'=>'superseded']);
                $schedule = null;
                if ($action === 'replace') {
                    $schedule = TuitionSchedule::where('status','published')->lockForUpdate()->find($scheduleId);
                    foreach (['academic_session_id','department_id','level','category','period'] as $field) {
                        if (! $schedule || (string) $schedule->{$field} !== (string) $invoice->{$field}) {
                            throw ValidationException::withMessages(['schedule_id'=>'Choose a published replacement for the same session, cohort and billing period.']);
                        }
                    }
                    if (TuitionInvoice::current()->where('user_id',$invoice->user_id)->where('academic_session_id',$invoice->academic_session_id)
                        ->whereIn('period',$invoice->period === 'Annual' ? ['Annual','First','Second'] : ['Annual',$invoice->period])->whereKeyNot($invoice->id)->exists()) {
                        throw ValidationException::withMessages(['action'=>'Another current invoice covers this period. Review it before replacement.']);
                    }
                }
                $invoice->update($review + ['cancelled_at'=>$invoice->cancelled_at ?? now(),'active_slot'=>null]);
                if ($schedule) {
                    $replacement = app(TuitionBilling::class)->issue($invoice->user, $schedule);
                    $invoice->update(['replacement_invoice_id'=>$replacement->id]);
                }
            }
            ActivityLogger::log($actor, 'withdrawn_tuition_'.$action, $reason, ['subject'=>$invoice,'target_user_id'=>$invoice->user_id,
                'properties'=>['before'=>$before,'after'=>$invoice->fresh()->toArray()]]);
            return $invoice->fresh();
        }, 3);
    }
}
