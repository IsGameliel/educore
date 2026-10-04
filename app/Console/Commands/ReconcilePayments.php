<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PaystackPayments;
use Illuminate\Console\Command;

class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile';
    protected $description = 'Recheck unsettled Paystack transactions without creating new charges';

    public function handle(PaystackPayments $gateway): int
    {
        \App\Services\OperationsHealth::record('payments', 'running');
        try {
            return $this->reconcile($gateway);
        } catch (\Throwable $exception) {
            \App\Services\OperationsHealth::record('payments', 'failed', 'Reconciliation interrupted. Check the server log.');
            report($exception);
            $this->error('Payment reconciliation could not complete.');
            return self::FAILURE;
        }
    }

    protected function reconcile(PaystackPayments $gateway): int
    {
        if (! config('services.paystack.secret_key')) {
            $this->warn('Paystack is not configured.');
            \App\Services\OperationsHealth::record('payments', 'failed', 'Paystack is not configured.');
            return self::FAILURE;
        }
        $confirmed = 0;
        $deferred = 0;
        Payment::whereIn('status', ['pending', 'abandoned', 'failed', 'reversed'])->where('created_at', '<', now()->subMinutes(2))
            ->where(fn ($q) => $q->whereNull('reconciliation_attempted_at')->orWhere('reconciliation_attempted_at', '<', now()->subMinutes(10)))
            ->orderBy('reconciliation_attempted_at')->orderBy('id')->limit(100)->get()->each(function ($payment) use ($gateway, &$confirmed, &$deferred) {
                $payment->update(['reconciliation_attempted_at' => now()]);
                try {
                    if ($gateway->verify($payment)->status === 'success') { $confirmed++; }
                } catch (\Throwable $exception) {
                    // Retry later and rotate through the queue without logging personal data.
                    $deferred++;
                }
            });
        // A separate rotating batch prevents old settled payments from starving new payments.
        Payment::where('purpose', 'tuition')->where('status', 'success')->whereNotNull('gateway_id')
            ->where(fn ($q) => $q->whereNull('financial_attempted_at')->orWhere('financial_attempted_at', '<', now()->subMinutes(10)))
            ->orderBy('financial_attempted_at')->orderBy('id')->limit(100)->get()->each(function ($payment) use ($gateway, &$deferred) {
                $payment->update(['financial_attempted_at' => now()]);
                try {
                    $payment = $gateway->verify($payment, true);
                    app(\App\Services\PaymentFinancialSync::class)->reconcile($payment);
                } catch (\Throwable $exception) {
                    $deferred++;
                }
            });
        $this->info("Confirmed: {$confirmed}; deferred: {$deferred}.");
        \App\Services\OperationsHealth::record('payments', $deferred ? 'warning' : 'success', "Confirmed: {$confirmed}; deferred: {$deferred}.");
        return self::SUCCESS;
    }
}
