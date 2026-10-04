<?php
namespace App\Services;

use App\Models\{Payment, PaymentSettlement};
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SettlementReconciliation
{
    public function run(string $from, string $to): int
    {
        return Cache::lock('paystack-settlements', 3600)->block(1, function () use ($from, $to) {
            $count = 0;
            foreach ($this->pages('settlement', ['from'=>$from, 'to'=>$to]) as $settlement) {
                $domain = str_starts_with((string) config('services.paystack.secret_key'), 'sk_live_') ? 'live' : 'test';
                if (($settlement['domain'] ?? null) !== $domain || ($settlement['currency'] ?? null) !== 'NGN'
                    || ! preg_match('/^\d+$/', (string) ($settlement['id'] ?? ''))) {
                    throw new RuntimeException('Settlement environment or currency does not match.');
                }
                $matched = 0; $gross = 0; $fees = 0; $exceptions = []; $seen = [];
                foreach ($this->pages('settlement/'.$settlement['id'].'/transactions') as $transaction) {
                    $reference = (string) ($transaction['reference'] ?? '');
                    if (isset($seen[$reference])) { throw new RuntimeException('Duplicate settlement transaction.'); }
                    $seen[$reference] = true;
                    $amount = $this->money($transaction['amount'] ?? null);
                    $gross += $amount;
                    $fees += $this->money($transaction['fees'] ?? null);
                    $payment = Payment::where('reference', $reference)->first();
                    if ($payment && $payment->status === 'success' && $payment->amount === $amount
                        && (string) $payment->gateway_id === (string) ($transaction['id'] ?? '')
                        && ($transaction['currency'] ?? null) === $payment->currency
                        && ($transaction['domain'] ?? null) === $domain && ($transaction['status'] ?? null) === 'success') {
                        $matched += $amount;
                    } else { $exceptions[] = $reference ?: 'Missing reference'; }
                }
                $reportedGross = $this->money($settlement['total_processed'] ?? null);
                $reportedFees = $this->money($settlement['total_fees'] ?? null);
                $net = $this->money($settlement['effective_amount'] ?? null);
                if ($gross !== $reportedGross || $fees !== $reportedFees) { $exceptions[] = 'Transaction totals differ from settlement totals'; }
                // Deductions/split payouts may explain a net variance; retain it for review.
                if ($reportedGross - $reportedFees !== $net) { $exceptions[] = 'Net payout requires deduction or split review'; }
                PaymentSettlement::updateOrCreate(['gateway_id'=>(string) $settlement['id']], [
                    'domain'=>$domain, 'currency'=>'NGN', 'status'=>$settlement['status'],
                    'gross'=>$reportedGross, 'fees'=>$reportedFees, 'net'=>$net, 'matched_amount'=>$matched,
                    'unmatched_count'=>count($exceptions), 'exceptions'=>$exceptions,
                    'settled_at'=>$settlement['settlement_date'] ?? null, 'checked_at'=>now(),
                ]);
                $count++;
            }
            OperationsHealth::record('settlements', 'success', "Reconciled {$count} settlements.");
            return $count;
        });
    }

    private function money($value): int
    {
        if (! is_int($value) || $value < 0) { throw new RuntimeException('Invalid settlement amount.'); }
        return $value;
    }

    private function pages(string $path, array $filters = []): \Generator
    {
        $key = config('services.paystack.secret_key');
        if (! $key) { throw new RuntimeException('Paystack is not configured.'); }
        for ($page = 1; $page <= 1000; $page++) {
            $response = Http::withToken($key)->acceptJson()->connectTimeout(5)->timeout(20)
                ->get('https://api.paystack.co/'.$path, $filters + ['page'=>$page, 'perPage'=>100])->throw()->json();
            if (($response['status'] ?? false) !== true || ! is_array($response['data'] ?? null)) {
                throw new RuntimeException('Invalid settlement response.');
            }
            foreach ($response['data'] as $record) { yield $record; }
            $pageCount = data_get($response, 'meta.pageCount');
            if ($pageCount !== null ? $page >= (int) $pageCount : count($response['data']) < 100) { return; }
        }
        throw new RuntimeException('Settlement pagination limit reached. Narrow the date window.');
    }
}
