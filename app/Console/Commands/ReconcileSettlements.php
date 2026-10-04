<?php
namespace App\Console\Commands;

use App\Services\SettlementReconciliation;
use Illuminate\Console\Command;

class ReconcileSettlements extends Command
{
    protected $signature = 'payments:settlements {--from=} {--to=}';
    protected $description = 'Reconcile Paystack payouts and transaction totals without changing student balances';
    public function handle(SettlementReconciliation $service): int
    {
        $from = $this->option('from') ?: today()->subDays(30)->format('Y-m-d');
        $to = $this->option('to') ?: today()->format('Y-m-d');
        $validator = validator(['from'=>$from, 'to'=>$to], ['from'=>'required|date_format:Y-m-d', 'to'=>'required|date_format:Y-m-d|after_or_equal:from']);
        if ($validator->fails()) { $this->error('Enter a valid date range (YYYY-MM-DD).'); return self::FAILURE; }
        try { $this->info('Reconciled settlements: '.$service->run($from, $to)); return self::SUCCESS; }
        catch (\Throwable $exception) {
            \App\Services\OperationsHealth::record('settlements', 'failed', 'Settlement reconciliation failed; retry and review gateway configuration.');
            $this->error('Settlement reconciliation unavailable. No student balances were changed.');
            return self::FAILURE;
        }
    }
}
