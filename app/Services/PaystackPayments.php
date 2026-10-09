<?php

namespace App\Services;

use App\Models\AdmissionApplication;
use App\Models\Payment;
use App\Models\ResultAppeal;
use App\Models\TranscriptRequest;
use App\Models\User;
use App\Models\TuitionCharge;
use App\Models\TuitionInvoice;
use App\Models\AcademicSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PaystackPayments
{
    public function create(Model $payable, User $payer, string $purpose): Payment
    {
        return Payment::firstOrCreate([
            'payable_type' => $payable->getMorphClass(), 'payable_id' => $payable->id,
        ], [
            'user_id' => $payer->id, 'email' => $payer->email, 'purpose' => $purpose,
            'amount' => $payable instanceof TuitionCharge ? $payable->amount : Payment::FEES[$purpose], 'currency' => 'NGN',
            'tuition_invoice_id' => $payable instanceof TuitionCharge ? $payable->tuition_invoice_id : null,
            'reference' => 'EDU-'.Str::uuid(), 'status' => 'pending',
        ]);
    }

    private function client()
    {
        $key = config('services.paystack.secret_key');
        if (! $key) {
            throw new RuntimeException('Paystack has not been configured.');
        }

        return Http::withToken($key)->acceptJson()->connectTimeout(5)->timeout(15);
    }

    public function checkout(Payment $payment): string
    {
        if ($payment->purpose === 'late_registration' && $payment->status !== 'success'
            && (! \App\Models\RegistrationSetting::current()->require_late_registration_fee || ! \App\Models\RegistrationSetting::current()->registration_open)) {
            throw new RuntimeException('Late registration payment is not currently required or registration is closed. Return to course registration.');
        }
        // Persist gateway intent before network I/O, including process crashes/timeouts.
        DB::transaction(function () use ($payment) {
            $invoice = null;
            if ($payment->tuition_invoice_id) {
                $invoice = TuitionInvoice::lockForUpdate()->findOrFail($payment->tuition_invoice_id);
            }
            $locked = Payment::lockForUpdate()->findOrFail($payment->id);
            if ($locked->status !== 'success' && $invoice && ! $invoice->isActive()) { throw new RuntimeException('Tuition invoice is withdrawn.'); }
            if ($locked->status !== 'success' && ! $locked->authorization_url) {
                if ($locked->purpose === 'tuition' && $locked->payable->status !== 'awaiting_payment') {
                    throw new RuntimeException('This payment attempt has been replaced.');
                }
                $this->client();
                $locked->update(['initialization_attempted_at'=>now()]);
            }
        }, 3);
        // Serializing initialization avoids creating two charges for a double click.
        return DB::transaction(function () use ($payment) {
            if ($payment->tuition_invoice_id) {
                TuitionInvoice::lockForUpdate()->findOrFail($payment->tuition_invoice_id);
            }
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === 'success') {
                return $payment->destination();
            }
            if ($payment->purpose === 'tuition' && ! TuitionInvoice::active()->whereKey($payment->tuition_invoice_id)->exists()) {
                throw new RuntimeException('This tuition schedule has been withdrawn. Further payments are disabled.');
            }
            if ($payment->purpose === 'tuition' && ($payment->tuitionInvoice->totals()['refund_review'] || $payment->tuitionInvoice->totals()['reversal_review'])) {
                throw new RuntimeException('Historical refunds require bursary reconciliation before checkout.');
            }
            if ($payment->purpose === 'tuition' && $payment->payable->status !== 'awaiting_payment') {
                throw new RuntimeException('This payment attempt has been replaced. Return to your tuition invoice.');
            }
            if ($payment->authorization_url) {
                return $payment->authorization_url;
            }
            $response = $this->client()->post('https://api.paystack.co/transaction/initialize', [
                'email' => $payment->email, 'amount' => $payment->amount,
                'currency' => $payment->currency, 'reference' => $payment->reference,
                'callback_url' => route('payments.callback'),
                'metadata' => ['payment_id' => $payment->id, 'purpose' => $payment->purpose],
            ])->throw()->json();
            $url = data_get($response, 'data.authorization_url');
            if (($response['status'] ?? false) !== true || ! is_string($url)
                || parse_url($url, PHP_URL_SCHEME) !== 'https'
                || parse_url($url, PHP_URL_HOST) !== 'checkout.paystack.com'
                || data_get($response, 'data.reference') !== $payment->reference) {
                throw new RuntimeException('Invalid Paystack initialization response.');
            }
            $payment->update(['authorization_url' => $url]);

            return $url;
        });
    }

    public function verify(Payment $payment, bool $recheck = false): Payment
    {
        if ($recheck && $payment->tuition_invoice_id) {
            // Serialize financial rechecks before fetching so an older response cannot
            // overwrite a newer reversal. All tuition writers use this lock order.
            return DB::transaction(function () use ($payment) {
                TuitionInvoice::lockForUpdate()->findOrFail($payment->tuition_invoice_id);
                return $this->verifyPayment($payment->fresh(), true);
            }, 3);
        }
        return $this->verifyPayment($payment, $recheck);
    }

    private function verifyPayment(Payment $payment, bool $recheck): Payment
    {
        if ($payment->status === 'success' && ! $recheck) {
            return $payment;
        }
        $response = $this->client()->get('https://api.paystack.co/transaction/verify/'.rawurlencode($payment->reference))->throw()->json();
        if (($response['status'] ?? false) !== true || ! is_array($response['data'] ?? null)) {
            throw new RuntimeException('Payment verification is unavailable.');
        }
        $data = $response['data'];
        $domain = str_starts_with((string) config('services.paystack.secret_key'), 'sk_live_') ? 'live' : 'test';
        if (($data['reference'] ?? null) !== $payment->reference
            || (string) ($data['amount'] ?? '') !== (string) $payment->amount
            || ($data['currency'] ?? null) !== $payment->currency
            || strtolower((string) data_get($data, 'customer.email')) !== strtolower($payment->email)
            || ($data['domain'] ?? null) !== $domain) {
            throw new RuntimeException('Payment verification details do not match.');
        }

        return DB::transaction(function () use ($payment, $data, $recheck) {
            // All tuition mutations acquire the invoice before its payments/charges.
            if ($payment->tuition_invoice_id) {
                TuitionInvoice::lockForUpdate()->findOrFail($payment->tuition_invoice_id);
            }
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === 'success') {
                if ((string) ($data['id'] ?? '') !== (string) $payment->gateway_id) {
                    throw new RuntimeException('Gateway transaction ID mismatch.');
                }
                // Preserve the original receipt and fulfillment. Only an explicit reversal
                // changes its financial contribution; transient failures never erase money.
                if ($recheck && $payment->purpose === 'tuition' && in_array($data['status'] ?? '', ['success', 'reversed'], true)) {
                    $payment->update(['gateway_reversed' => $data['status'] === 'reversed']);
                    app(PaymentFinancialSync::class)->recalculate($payment);
                }
                if ($recheck && $payment->purpose === 'late_registration' && in_array($data['status'] ?? '', ['success', 'reversed'], true)) {
                    $payment->update(['gateway_reversed' => $data['status'] === 'reversed']);
                }
                $payment->update(['verified_at' => now()]);
                return $payment;
            }
            $status = $data['status'] ?? 'pending';
            $settledTuitionReversal = $status === 'reversed' && $payment->purpose === 'tuition' && ! empty($data['paid_at']);
            if ($status !== 'success' && ! $settledTuitionReversal) {
                $payment->update([
                    'status' => in_array($status, ['failed', 'abandoned', 'reversed'], true) ? $status : 'pending',
                    'verified_at' => now(),
                ]);
                if ($status === 'reversed' && ! empty($data['id'])) {
                    $payment->update(['gateway_id' => (string) $data['id'], 'gateway_reversed' => true]);
                }

                return $payment;
            }
            if (empty($data['id'])) {
                throw new RuntimeException('Missing gateway transaction ID.');
            }
            $payable = $payment->payable()->lockForUpdate()->firstOrFail();
            if ($payable instanceof TuitionCharge) {
                // Lock the invoice so installments and concurrent verification are serialized.
                TuitionInvoice::lockForUpdate()->findOrFail($payable->tuition_invoice_id);
            }
            if ($payable->status !== 'awaiting_payment' && ! ($payable instanceof TuitionCharge && $payable->status === 'superseded')) {
                throw new RuntimeException('This request is not awaiting payment.');
            }
            if ($payable instanceof AdmissionApplication) {
                $user = User::lockForUpdate()->findOrFail($payable->user_id);
                if (! $user->isAdmissionApplicant()) {
                    throw new RuntimeException('Applicant account requires review.');
                }
                $payable->update(['status' => 'completed', 'completed_at' => now()]);
                $user->forceFill([
                    'name' => trim($payable->first_name.' '.$payable->middle_name.' '.$payable->surname),
                    'usertype' => 'student', 'department_id' => $payable->department_id,
                    'level' => $payable->level, 'entry_year' => $payable->entry_year,
                ])->save();
                if ($session = AcademicSession::current()) {
                    app(TuitionBilling::class)->ensureInvoices($user, $session);
                }
            } elseif ($payable instanceof TranscriptRequest) {
                $payable->update(['status' => 'pending']);
            } elseif ($payable instanceof ResultAppeal) {
                $payable->update(['status' => 'open']);
            } elseif ($payable instanceof TuitionCharge) {
                $payable->update(['status' => 'paid']);
            } elseif ($payable instanceof \App\Models\LateRegistrationCharge) {
                $payable->update(['status' => 'paid']);
            } else {
                throw new RuntimeException('Unsupported payment purpose.');
            }
            $payment->update([
                'status' => 'success', 'gateway_id' => (string) $data['id'],
                'channel' => $data['channel'] ?? null, 'paid_at' => $data['paid_at'] ?? now(),
                'verified_at' => now(),
                'gateway_reversed' => $settledTuitionReversal,
            ]);
            if ($payment->purpose === 'tuition') { app(PaymentFinancialSync::class)->recalculate($payment); }

            return $payment;
        }, 3);
    }
}
