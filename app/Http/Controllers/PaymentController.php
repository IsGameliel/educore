<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaystackPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function receipts(Request $request)
    {
        $payments = Payment::where('user_id', $request->user()->id)->where('status', 'success')->latest('paid_at')->paginate(20);
        return view('payments.receipts', compact('payments'));
    }

    public function receipt(Request $request, Payment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id || in_array($request->user()->dashboardRole(), ['admin', 'bursar', 'accountant'], true), 403);
        abort_unless($payment->status === 'success', 404);
        $payment->load(['user', 'tuitionInvoice']);
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('payments.receipt-pdf', compact('payment'))
            ->setOption('isRemoteEnabled', false)->download($payment->receiptNumber().'.pdf');
    }

    public function index(Request $request)
    {
        $payments = Payment::where('user_id', $request->user()->id)->latest()->paginate(20);

        return view('payments.index', compact('payments'));
    }

    public function show(Request $request, Payment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        return view('payments.show', compact('payment'));
    }

    public function checkout(Request $request, Payment $payment, PaystackPayments $gateway)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        return $this->start($payment, $gateway);
    }

    public function start(Payment $payment, PaystackPayments $gateway)
    {
        try {
            return redirect()->away($gateway->checkout($payment));
        } catch (\Throwable $exception) {
            // Do not log gateway payloads, credentials or card data.
            Log::warning('Payment checkout unavailable.', ['reference' => $payment->reference, 'exception_type' => get_class($exception)]);

            return redirect()->route('payments.show', $payment)->withErrors([
                'payment' => 'Your request is saved, but checkout is unavailable. Please try again shortly or contact administration with your payment reference.',
            ]);
        }
    }

    public function callback(Request $request, PaystackPayments $gateway)
    {
        $data = $request->validate(['reference' => 'required|string|max:80']);
        $payment = Payment::where('reference', $data['reference'])->where('user_id', $request->user()->id)->firstOrFail();

        return $this->check($payment, $gateway);
    }

    public function refresh(Request $request, Payment $payment, PaystackPayments $gateway)
    {
        abort_unless($payment->user_id === $request->user()->id || in_array($request->user()->dashboardRole(), ['admin', 'bursar', 'accountant'], true), 403);
        if (in_array($request->user()->dashboardRole(), ['admin', 'bursar', 'accountant'], true)) {
            try {
                $payment = $gateway->verify($payment, true);
                app(\App\Services\PaymentFinancialSync::class)->reconcile($payment);
            } catch (\Throwable $exception) {
                return back()->withErrors(['payment' => 'Verification is unavailable or the payment details do not match. Please retry later.']);
            }

            return back()->with('success', 'Payment status checked with Paystack.');
        }

        return $this->check($payment, $gateway, true);
    }

    private function check(Payment $payment, PaystackPayments $gateway, bool $recheck = false)
    {
        try {
            $payment = $gateway->verify($payment, $recheck && $payment->purpose === 'tuition');
            if ($recheck) { app(\App\Services\PaymentFinancialSync::class)->reconcile($payment); }
        } catch (\Throwable $exception) {
            return redirect()->route('payments.show', $payment)->withErrors(['payment' => 'We could not confirm this payment yet. If you were charged, check the status again before paying.']);
        }
        if ($payment->status === 'success') {
            if ($payment->fresh()->gateway_deduction || $payment->fresh()->dispute_open || $payment->fresh()->gateway_reversed) {
                return redirect($payment->destination())->with('success', 'Payment checked. Refunds, reversals and dispute review are reflected in your current tuition balance and clearance.');
            }
            return redirect($payment->destination())->with('success', $payment->purpose === 'tuition' ? 'Tuition payment confirmed. Your balance and registration clearance have been updated.' : 'Payment confirmed. Your request has been submitted successfully.');
        }

        return redirect()->route('payments.show', $payment)->withErrors(['payment' => 'Payment has not been completed. Your request remains saved until payment is confirmed.']);
    }

    public function webhook(Request $request, PaystackPayments $gateway)
    {
        $key = config('services.paystack.secret_key');
        abort_unless($key && hash_equals(hash_hmac('sha512', $request->getContent(), $key), (string) $request->header('x-paystack-signature')), 401);
        $event = $request->input('event');
        if (in_array($event, ['refund.pending', 'refund.processing', 'refund.processed', 'refund.failed',
            'charge.dispute.create', 'charge.dispute.remind', 'charge.dispute.resolve'], true)) {
            try {
                $id = $request->input('data.id');
                if (! is_int($id) && ! is_string($id)) { throw new \RuntimeException('Missing gateway record ID.'); }
                $sync = app(\App\Services\PaymentFinancialSync::class);
                str_starts_with($event, 'refund.') ? $sync->refund((string) $id) : $sync->dispute((string) $id);
            } catch (\Throwable $exception) {
                Log::warning('Payment financial webhook deferred.', ['event' => $event, 'exception_type' => get_class($exception)]);
                return response()->json(['retry' => true], 503);
            }
            return response()->json(['received' => true]);
        }
        if ($request->input('event') !== 'charge.success') {
            return response()->json(['received' => true]);
        }
        $reference = $request->input('data.reference');
        if (! is_string($reference)) {
            return response()->json(['received' => true]);
        }
        $payment = Payment::where('reference', $reference)->first();
        if ($payment) {
            try {
                // Verify independently; webhook delivery and callback may race.
                if ($gateway->verify($payment)->status !== 'success') {
                    return response()->json(['retry' => true], 503);
                }
            } catch (\Throwable $exception) {
                Log::warning('Payment webhook verification deferred.', ['reference' => $reference, 'exception_type' => get_class($exception)]);

                return response()->json(['retry' => true], 503);
            }
        }

        return response()->json(['received' => true]);
    }
}
