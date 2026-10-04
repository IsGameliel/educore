<?php
namespace App\Services;

use App\Jobs\SendTuitionReminder;
use App\Models\{TuitionInvoice, TuitionReminder};
use Illuminate\Support\Facades\DB;

class TuitionReminders
{
    public static function notice(TuitionInvoice $invoice): ?array
    {
        if (! $invoice->isActive()) { return null; }
        $totals = $invoice->totals();
        if ($totals['balance'] <= 0) { return null; }
        $amount = max(0, $invoice->requiredAmount('First') - $totals['paid']);
        $date = $invoice->due_date;
        $part = 'first';
        if ($amount <= 0) {
            $date = $invoice->second_due_date;
            $amount = $totals['balance'];
            $part = 'balance';
        }
        if (! $date) { return null; }
        $days = (int) today()->diffInDays($date, false);
        if ($days > 7) { return null; }
        $kind = $days > 0 ? 'upcoming' : ($days === 0 ? 'due_today' : 'overdue');
        $bucket = $days < 0 ? intdiv(abs($days) - 1, 7) : 0;
        return ['key'=>$part.'_'.$date->format('Y-m-d').'_'.$kind.'_'.$bucket, 'kind'=>$kind, 'date'=>$date->format('Y-m-d'),
            'amount'=>$amount, 'label'=>match ($kind) { 'upcoming'=>'Tuition payment due soon', 'due_today'=>'Tuition payment due today', default=>'Tuition payment overdue' }];
    }

    public function queue(TuitionInvoice $invoice): bool
    {
        if (! config('tuition.email_reminders')) { return false; }
        return DB::transaction(function () use ($invoice) {
            $invoice = TuitionInvoice::lockForUpdate()->findOrFail($invoice->id);
            $notice = self::notice($invoice);
            if (! $notice || ! $invoice->user?->email || $invoice->user->dashboardRole() !== 'student') { return false; }
            $reminder = TuitionReminder::firstOrCreate(['tuition_invoice_id'=>$invoice->id,'notice_key'=>$notice['key']]);
            if (in_array($reminder->status, ['sent','skipped']) || ($reminder->status === 'queued' && $reminder->queued_at?->gt(now()->subDay()))) { return false; }
            $reminder->update(['status'=>'queued','queued_at'=>now()]);
            SendTuitionReminder::dispatch($reminder->id)->afterCommit();
            return true;
        }, 3);
    }
}
