<?php

namespace App\Services\Tenancy;

use App\Models\Central\Institution;
use App\Models\Central\SubscriptionInvoice;
use App\Models\Central\SubscriptionPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SubscriptionPayments
{
    private function client()
    {
        $key = config('tenancy.paystack_secret_key');
        if (! is_string($key) || ! preg_match('/^sk_(test|live)_/', $key)) {
            throw new RuntimeException('Institution subscription payments are not configured. Please contact the platform administrator.');
        }

        return Http::withToken($key)->acceptJson()->connectTimeout(5)->timeout(20)->baseUrl('https://api.paystack.co');
    }

    public function checkout(SubscriptionInvoice $invoice): string
    {
        $this->client();

        return DB::connection(config('tenancy.central_connection'))->transaction(function () use ($invoice) {
            $invoice = SubscriptionInvoice::lockForUpdate()->findOrFail($invoice->id);
            if ($invoice->status === 'paid') {
                throw new RuntimeException('This semester invoice is already paid.');
            }
            if ($invoice->semester_end && $invoice->semester_end->format('Y-m-d') < now(Institution::findOrFail($invoice->institution_id)->timezone)->toDateString()) {
                throw new RuntimeException('This semester has ended. Contact support to resolve the expired invoice before renewing.');
            }
            $pending = SubscriptionPayment::where('invoice_id', $invoice->id)->where('status', 'pending')->latest()->first();
            if ($pending?->checkout_url) {
                return $pending->checkout_url;
            }
            $institution = Institution::findOrFail($invoice->institution_id);
            if ($institution->status !== 'ready') {
                throw new RuntimeException('Complete school setup before proceeding with payment.');
            }
            $payment = SubscriptionPayment::create(['invoice_id' => $invoice->id,
                'reference' => 'euvion-sub-'.Str::uuid(), 'email' => $institution->owner_email]);
            try {
                $response = $this->client()->post('/transaction/initialize', [
                    'email' => $payment->email, 'amount' => $invoice->amount_minor, 'currency' => $invoice->currency,
                    'reference' => $payment->reference,
                    'callback_url' => rtrim(config('tenancy.platform_url'), '/').'/subscribe/callback',
                    'metadata' => ['invoice_id' => $invoice->id, 'institution_id' => $invoice->institution_id],
                ])->throw()->json();
                $url = data_get($response, 'data.authorization_url');
                if (($response['status'] ?? false) !== true || data_get($response, 'data.reference') !== $payment->reference
                    || ! is_string($url) || parse_url($url, PHP_URL_SCHEME) !== 'https'
                    || parse_url($url, PHP_URL_HOST) !== 'checkout.paystack.com' || parse_url($url, PHP_URL_USER)) {
                    throw new RuntimeException('Payment checkout is unavailable.');
                }
                $payment->update(['checkout_url' => $url, 'status' => 'pending']);

                return $url;
            } catch (\Throwable $exception) {
                // The transaction rolls back; a retry gets a fresh unique gateway reference.
                throw new RuntimeException('Unable to open payment checkout. Your account and invoice are saved; please retry.', previous: $exception);
            }
        });
    }

    public function verify(SubscriptionPayment $payment): SubscriptionInvoice
    {
        $invoice = SubscriptionInvoice::findOrFail($payment->invoice_id);
        $response = $this->client()->get('/transaction/verify/'.rawurlencode($payment->reference))->throw()->json();
        $data = $response['data'] ?? [];
        $domain = str_starts_with(config('tenancy.paystack_secret_key'), 'sk_live_') ? 'live' : 'test';
        if (($response['status'] ?? false) !== true || ! is_array($data)
            || ($data['reference'] ?? null) !== $payment->reference
            || (string) ($data['amount'] ?? '') !== (string) $invoice->amount_minor
            || ($data['currency'] ?? null) !== $invoice->currency
            || strtolower((string) data_get($data, 'customer.email')) !== strtolower($payment->email)
            || ($data['domain'] ?? null) !== $domain) {
            throw new RuntimeException('Payment verification details do not match this invoice.');
        }
        if (in_array($data['status'] ?? '', ['failed', 'abandoned', 'reversed'], true) && $payment->status !== 'success') {
            $payment->update(['status' => 'failed']);
        }
        if (($data['status'] ?? '') !== 'success') {
            throw new RuntimeException('Payment has not been confirmed as successful. You can check again shortly.');
        }
        if (! isset($data['id']) || ! is_scalar($data['id'])) {
            throw new RuntimeException('Payment verification is missing its transaction identifier.');
        }

        return DB::connection(config('tenancy.central_connection'))->transaction(function () use ($invoice, $payment, $data) {
            $invoice = SubscriptionInvoice::lockForUpdate()->findOrFail($invoice->id);
            $payment = SubscriptionPayment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === 'success' && $payment->gateway_id !== (string) $data['id']) {
                throw new RuntimeException('Payment transaction identifier does not match.');
            }
            $payment->update(['status' => 'success', 'gateway_id' => (string) $data['id'], 'verified_at' => now()]);
            if ($invoice->status !== 'paid') {
                $invoice->update(['status' => 'paid', 'paid_at' => now()]);
                app(SemesterBilling::class)->record($invoice->institution_id, 'payment_verified', ['invoice_id' => $invoice->id, 'payment_id' => $payment->id]);
                Institution::whereKey($invoice->institution_id)->update(['subscription_status' => 'active']);
            }

            return $invoice;
        });
    }
}
