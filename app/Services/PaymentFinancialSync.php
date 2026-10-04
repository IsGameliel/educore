<?php

namespace App\Services;

use App\Models\{Payment, PaymentGatewayChange, TuitionAdjustment, TuitionInvoice};
use App\Support\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Http};
use RuntimeException;

class PaymentFinancialSync
{
    private function fetch(string $path, array $query = []): array
    {
        $key = config('services.paystack.secret_key');
        if (! $key) { throw new RuntimeException('Paystack has not been configured.'); }
        $response = Http::withToken($key)->acceptJson()->connectTimeout(5)->timeout(15)
            ->get('https://api.paystack.co/'.$path, $query)->throw()->json();
        if (($response['status'] ?? false) !== true || ! is_array($response['data'] ?? null)) {
            throw new RuntimeException('Financial verification is unavailable.');
        }
        return $response;
    }

    public function refund(string $id, ?TuitionInvoice $invoice = null, ?int $amount = null): ?PaymentGatewayChange
    {
        $this->assertId($id);
        return $this->apply('refund', $id, $this->fetch('refund/'.$id)['data'], $invoice, $amount);
    }

    public function dispute(string $id): ?PaymentGatewayChange
    {
        $this->assertId($id);
        return $this->apply('dispute', $id, $this->fetch('dispute/'.$id)['data']);
    }

    private function assertId(string $id): void
    {
        if (! preg_match('/^[1-9][0-9]{0,19}$/', $id)) { throw new RuntimeException('Invalid gateway record ID.'); }
    }

    private function apply(string $kind, string $id, array $data, ?TuitionInvoice $invoice = null, ?int $expectedAmount = null, ?int $expectedPaymentId = null): ?PaymentGatewayChange
    {
        if ((string) ($data['id'] ?? '') !== $id) { throw new RuntimeException('Gateway record ID mismatch.'); }
        $transaction = $data['transaction'] ?? null;
        $transactionId = is_array($transaction) ? ($transaction['id'] ?? null) : $transaction;
        if (! is_scalar($transactionId) || ! ctype_digit((string) $transactionId)) {
            throw new RuntimeException('Missing gateway transaction ID.');
        }
        $payment = Payment::where('gateway_id', (string) $transactionId)->first();
        // A refund/dispute can arrive before the original success callback.
        if (! $payment && is_array($transaction) && is_string($transaction['reference'] ?? null)) {
            $payment = Payment::where('reference', $transaction['reference'])->first();
        }
        if (! $payment) {
            if ($invoice) { throw new RuntimeException('Refund does not belong to this invoice.'); }
            // Numeric refund transactions do not contain a reference. Fetch it before deciding it is unrelated.
            $remote = $this->fetch('transaction/'.$transactionId)['data'];
            $payment = Payment::where('reference', $remote['reference'] ?? '')->first();
            if (! $payment) { return null; }
        }
        if ($expectedPaymentId !== null && $payment->id !== $expectedPaymentId) {
            throw new RuntimeException('Financial list transaction mismatch.');
        }
        if ($payment->purpose !== 'tuition' || ($invoice && $payment->tuition_invoice_id !== $invoice->id)) {
            if ($invoice) { throw new RuntimeException('Refund does not belong to this invoice.'); }
            return null;
        }
        $domain = str_starts_with((string) config('services.paystack.secret_key'), 'sk_live_') ? 'live' : 'test';
        $currency = $data['currency'] ?? (is_array($transaction) ? ($transaction['currency'] ?? null) : null);
        $amount = $kind === 'refund' ? ($data['amount'] ?? null) : ($data['refund_amount'] ?? 0);
        if (($data['domain'] ?? null) !== $domain || $currency !== $payment->currency
            || ! is_scalar($amount) || ! ctype_digit((string) $amount) || (int) $amount > $payment->amount
            || ($kind === 'refund' && (int) $amount === 0)
            || ($expectedAmount !== null && ((int) $amount !== $expectedAmount || ($data['status'] ?? '') !== 'processed'))
            || (is_array($transaction) && isset($transaction['reference']) && $transaction['reference'] !== $payment->reference)) {
            throw new RuntimeException('Gateway financial details do not match.');
        }
        if ($payment->status !== 'success') { $payment = app(PaystackPayments::class)->verify($payment); }
        if ((string) $payment->gateway_id !== (string) $transactionId || ! in_array($payment->status, ['success', 'reversed'], true)) {
            throw new RuntimeException('Original payment requires verification before financial synchronization.');
        }
        $statuses = $kind === 'refund' ? ['pending', 'processing', 'processed', 'failed', 'needs-attention']
            : ['pending', 'awaiting-merchant-feedback', 'awaiting-bank-feedback', 'resolved', 'archived'];
        if (! in_array($data['status'] ?? null, $statuses, true)) { throw new RuntimeException('Unknown gateway financial status.'); }
        $updated = $data['updatedAt'] ?? $data['updated_at'] ?? null;
        if (! is_string($updated) || $updated === '') { throw new RuntimeException('Missing gateway financial timestamp.'); }
        $updated = Carbon::parse($updated)->setTimezone(config('app.timezone', 'UTC'));
        return DB::transaction(function () use ($kind, $id, $data, $payment, $amount, $updated) {
            TuitionInvoice::lockForUpdate()->findOrFail($payment->tuition_invoice_id);
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            $record = PaymentGatewayChange::firstOrNew(['kind' => $kind, 'gateway_id' => $id]);
            if ($record->exists && $record->payment_id !== $payment->id) { throw new RuntimeException('Gateway record belongs to another payment.'); }
            if ($record->gateway_updated_at && $record->gateway_updated_at->gt($updated)) { return $record; }
            $before = $record->exists ? $record->toArray() : null;
            $record->fill(['payment_id' => $payment->id, 'status' => $data['status'], 'amount' => (int) $amount,
                'resolution' => $kind === 'dispute' ? ($data['resolution'] ?? null) : null,
                'dispute_id' => $kind === 'refund' && ! empty($data['dispute'])
                    ? (string) (is_array($data['dispute']) ? $data['dispute']['id'] : $data['dispute']) : null,
                'gateway_updated_at' => $updated]);
            if ($record->isDirty()) {
                $record->save();
                ActivityLogger::log(null, 'payment_'.$kind.'_synchronized', 'Verified Paystack '.$kind.' status: '.$record->status, [
                    'subject' => $record, 'target_user_id' => $payment->user_id,
                    'properties' => ['before' => $before, 'after' => $record->toArray()],
                ]);
            }
            // Match legacy entries only by exact gateway ID; never guess by amount.
            if ($kind === 'refund') {
                $legacy = TuitionAdjustment::where('tuition_invoice_id', $payment->tuition_invoice_id)->where('type', 'refund')
                    ->where('external_reference', $id)->whereNull('gateway_change_id')->first();
                if ($legacy) {
                    if ($legacy->amount !== (int) $amount) { throw new RuntimeException('Legacy refund amount requires bursary review.'); }
                    $legacy->update(['gateway_change_id' => $record->id]);
                }
            }
            $this->recalculate($payment);
            return $record;
        }, 3);
    }

    // Caller holds the invoice/payment locks. Refunds linked to disputes are counted once.
    public function recalculate(Payment $payment): void
    {
        $records = $payment->gatewayChanges()->get();
        $refunds = $records->where('kind', 'refund')->where('status', 'processed');
        $deduction = (int) $refunds->sum('amount');
        $open = false;
        foreach ($records->where('kind', 'dispute') as $dispute) {
            if (in_array($dispute->status, ['resolved', 'archived'], true) && $dispute->resolution === 'merchant-accepted') {
                $deduction += max(0, $dispute->amount - (int) $refunds->where('dispute_id', $dispute->gateway_id)->sum('amount'));
            } elseif (! in_array($dispute->status, ['resolved', 'archived'], true)
                || ($dispute->status === 'resolved' && $dispute->resolution !== 'declined')) {
                $open = true; // Unknown resolutions require review, never silently release clearance.
            }
        }
        // Reversed transactions can be partially refunded. Deduct verified amounts only.
        $payment->update(['gateway_deduction' => min($payment->amount, $deduction), 'dispute_open' => $open]);
    }

    public function reconcile(Payment $payment): void
    {
        if ($payment->purpose !== 'tuition' || $payment->status !== 'success' || ! $payment->gateway_id) { return; }
        $seen = [];
        foreach (['refund', 'dispute'] as $kind) {
            for ($page = 1; ; $page++) {
                if ($page > 100) { throw new RuntimeException('Financial pagination requires review.'); }
                $response = $this->fetch($kind, ['transaction' => $payment->gateway_id, 'perPage' => 100, 'page' => $page]);
                if (! array_is_list($response['data'])) { throw new RuntimeException('Invalid financial list.'); }
                foreach ($response['data'] as $item) {
                    $id = (string) ($item['id'] ?? '');
                    $this->assertId($id);
                    $this->apply($kind, $id, $this->fetch($kind.'/'.$id)['data'], null, null, $payment->id);
                    $seen[$kind.':'.$id] = true;
                }
                if (isset($response['meta']['pageCount']) ? $page >= (int) $response['meta']['pageCount'] : count($response['data']) < 100) { break; }
            }
        }
        // Revisit known records even if a list endpoint temporarily omits them.
        foreach ($payment->gatewayChanges()->get() as $record) {
            if (isset($seen[$record->kind.':'.$record->gateway_id])) { continue; }
            $record->kind === 'refund' ? $this->refund($record->gateway_id) : $this->dispute($record->gateway_id);
        }
        $payment->update(['financial_checked_at' => now()]);
    }
}
