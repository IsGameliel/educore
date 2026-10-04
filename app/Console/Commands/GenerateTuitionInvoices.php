<?php
namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Services\{OperationsHealth, TuitionBilling};
use Illuminate\Console\Command;

class GenerateTuitionInvoices extends Command
{
    protected $signature = 'tuition:generate';
    protected $description = 'Generate missing active-session invoices without changing issued bills';
    public function handle(TuitionBilling $billing): int
    {
        OperationsHealth::record('billing', 'running');
        try {
            if ($session = AcademicSession::current()) { $billing->generateSession($session); }
            OperationsHealth::record('billing', 'success', $session ? 'Missing invoice check completed.' : 'No active academic session.');
            $this->info('Missing invoice check completed. Existing bills were preserved.');
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            OperationsHealth::record('billing', 'failed', 'Invoice generation failed. Review enrollment and the server log.');
            return self::FAILURE;
        }
    }
}
