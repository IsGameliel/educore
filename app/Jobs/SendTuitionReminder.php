<?php
namespace App\Jobs;

use App\Mail\TuitionPaymentReminder;
use App\Models\{TuitionInvoice, TuitionReminder};
use App\Services\{OperationsHealth, TuitionReminders};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\{DB, Mail};

class SendTuitionReminder implements ShouldQueue
{
    use Queueable;
    public int $tries = 3;
    public int $timeout = 60;
    public function __construct(public int $reminderId) {}
    public function backoff(): array { return [60, 300, 900]; }

    public function handle(): void
    {
        $record = TuitionReminder::find($this->reminderId);
        if (! $record) { return; }
        DB::transaction(function () use ($record) {
            $invoice = TuitionInvoice::lockForUpdate()->findOrFail($record->tuition_invoice_id);
            $reminder = TuitionReminder::lockForUpdate()->findOrFail($record->id);
            if (in_array($reminder->status, ['sent','skipped'])) { return; }
            $notice = TuitionReminders::notice($invoice);
            if (! config('tuition.email_reminders') || ! $notice || $notice['key'] !== $reminder->notice_key
                || ! $invoice->user?->email || $invoice->user->dashboardRole() !== 'student') {
                $reminder->update(['status'=>'skipped']);
                return;
            }
            Mail::to($invoice->user->email)->send(new TuitionPaymentReminder($invoice->number, $invoice->user->name, $notice, route('tuition.show', $invoice)));
            $reminder->update(['status'=>'sent','sent_at'=>now()]);
        });
    }

    public function failed(?\Throwable $exception): void
    {
        TuitionReminder::whereKey($this->reminderId)->where('status','!=','sent')->update(['status'=>'failed']);
        OperationsHealth::record('reminders', 'warning', 'A reminder email failed. Review queued jobs and mail configuration.');
    }
}
