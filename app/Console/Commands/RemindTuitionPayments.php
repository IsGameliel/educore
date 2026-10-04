<?php
namespace App\Console\Commands;

use App\Models\TuitionInvoice;
use App\Services\{OperationsHealth, TuitionReminders};
use Illuminate\Console\Command;

class RemindTuitionPayments extends Command
{
    protected $signature = 'tuition:remind {--dry-run : Count eligible reminders without queuing email}';
    protected $description = 'Queue upcoming, due-day and weekly overdue tuition reminders';
    public function handle(TuitionReminders $reminders): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry) { OperationsHealth::record('reminders','running'); }
        try {
            $count = 0;
            TuitionInvoice::active()->with(['payments','adjustments','user'])->chunkById(100, function ($invoices) use ($reminders, $dry, &$count) {
                foreach ($invoices as $invoice) {
                    if ($dry ? TuitionReminders::notice($invoice) !== null : $reminders->queue($invoice)) { $count++; }
                }
            });
            $this->info(($dry ? 'Eligible invoices: ' : 'Emails queued: ').$count);
            if (! $dry) { OperationsHealth::record('reminders', config('tuition.email_reminders') ? 'success' : 'warning', config('tuition.email_reminders') ? $count.' reminder(s) queued; delivery tracked separately.' : 'Email reminders are disabled. Dashboard notices remain available.'); }
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            if (! $dry) { OperationsHealth::record('reminders','failed','Reminder processing failed. Check the server log.'); }
            return self::FAILURE;
        }
    }
}
