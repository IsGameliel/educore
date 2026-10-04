<?php
namespace App\Services;

use App\Models\{FinancialApproval, TuitionInvoice, User};
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialApprovals
{
    public function request(TuitionInvoice $invoice, User $actor, array $data): FinancialApproval
    {
        abort_unless(in_array($actor->dashboardRole(), ['admin','bursar'], true), 403);
        $approval = DB::transaction(function () use ($invoice, $actor, $data) {
            $invoice = TuitionInvoice::lockForUpdate()->findOrFail($invoice->id);
            $this->validateAmount($invoice, $data['amount']);
            $approval = FinancialApproval::firstOrCreate($data + ['tuition_invoice_id'=>$invoice->id, 'requested_by'=>$actor->id, 'status'=>'pending']);
            if ($approval->wasRecentlyCreated) {
                ActivityLogger::log($actor, 'financial_approval_requested', $data['reason'], ['subject'=>$approval, 'target_user_id'=>$invoice->user_id]);
            }
            return $approval;
        }, 3);
        if ($approval->wasRecentlyCreated) { $this->notify($approval, User::where('usertype','admin')->whereKeyNot($actor->id)->get()); }
        return $approval;
    }

    public function review(FinancialApproval $approval, User $actor, string $decision, string $reason): void
    {
        abort_unless($actor->dashboardRole() === 'admin', 403);
        if (! in_array($decision, ['approved','rejected'], true)) { throw ValidationException::withMessages(['decision'=>'Choose approve or reject.']); }
        DB::transaction(function () use ($approval, $actor, $decision, $reason) {
            $invoice = TuitionInvoice::lockForUpdate()->findOrFail($approval->tuition_invoice_id);
            $approval = FinancialApproval::lockForUpdate()->findOrFail($approval->id);
            abort_if($approval->requested_by === $actor->id, 403, 'A different administrator must review this request.');
            if ($approval->status !== 'pending') {
                throw ValidationException::withMessages(['approval'=>'This request has already been reviewed.']);
            }
            if ($decision === 'approved') {
                $this->validateAmount($invoice, $approval->amount);
                $invoice->adjustments()->create(['type'=>$approval->type, 'amount'=>$approval->amount,
                    'reason'=>$approval->reason, 'recorded_by'=>$actor->id]);
            }
            $approval->update(['status'=>$decision, 'review_reason'=>$reason, 'reviewed_by'=>$actor->id, 'reviewed_at'=>now()]);
            ActivityLogger::log($actor, 'financial_approval_'.$decision, $reason, ['subject'=>$approval, 'target_user_id'=>$invoice->user_id]);
        }, 3);
        $approval->refresh();
        $this->notify($approval, collect([$approval->requester, $approval->invoice->user])->unique('id'));
    }

    private function notify(FinancialApproval $approval, $recipients): void
    {
        try { \Illuminate\Support\Facades\Notification::send($recipients, new \App\Notifications\FinancialApprovalUpdate($approval->id)); }
        catch (\Throwable $exception) { OperationsHealth::record('finance_notifications', 'failed', 'Finance decision saved but email could not be queued. Review the approval queue.'); }
    }

    private function validateAmount(TuitionInvoice $invoice, int $amount): void
    {
        if ($invoice->cancelled_at || $amount <= 0 || $amount > $invoice->totals()['due']) {
            throw ValidationException::withMessages(['amount'=>'The adjustment must be positive and within the current tuition charge on an uncancelled invoice.']);
        }
    }
}
