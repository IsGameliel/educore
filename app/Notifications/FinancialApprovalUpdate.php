<?php
namespace App\Notifications;

use App\Models\FinancialApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class FinancialApprovalUpdate extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3;
    public function __construct(public int $approvalId) { $this->afterCommit(); }
    public function via(object $notifiable): array { return ['mail']; }
    public function failed(\Throwable $exception): void
    {
        \App\Services\OperationsHealth::record('finance_notifications', 'failed', 'A financial approval email exhausted its retries. Review the approval queue and mail configuration.');
    }
    public function toMail(object $notifiable): MailMessage
    {
        $approval = FinancialApproval::with('invoice')->findOrFail($this->approvalId);
        $student = $notifiable->id === $approval->invoice->user_id;
        return (new MailMessage)->subject('Tuition adjustment '.$approval->status)
            ->line('Invoice '.$approval->invoice->number.': '.$approval->type.' of NGN '.number_format($approval->amount/100, 2).' is '.$approval->status.'.')
            ->line($approval->status === 'pending' ? 'The balance changes after independent approval.' : 'Your invoice reflects the recorded decision.')
            ->action('Review details', $student ? route('tuition.show', $approval->invoice) : route('finance.controls'));
    }
}
