<?php

namespace App\Http\Controllers;

use App\Models\{AcademicSession, TuitionInvoice};
use App\Services\{PaystackPayments, TuitionBilling};
use Illuminate\Http\Request;

class TuitionController extends Controller
{
    public function index(Request $request, TuitionBilling $billing)
    {
        $session = AcademicSession::current();
        if ($session) {
            $billing->ensureInvoices($request->user(), $session);
        }
        $invoices = TuitionInvoice::active()->with(['payments', 'adjustments'])->where('user_id', $request->user()->id)->latest()->paginate(15);
        $clearances = $session ? collect(['First', 'Second'])->mapWithKeys(fn ($semester) => [$semester => \App\Services\Academic\RegistrationAccess::status($request->user(), $session->name, $semester)])->all() : [];

        return view('tuition.index', compact('invoices', 'session', 'clearances'));
    }

    public function show(Request $request, TuitionInvoice $invoice)
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);
        $invoice->load(['payments', 'adjustments']);
        return view('tuition.show', ['invoice' => $invoice, 'totals' => $invoice->totals()]);
    }

    public function pay(Request $request, TuitionInvoice $invoice, TuitionBilling $billing, PaystackPayments $gateway)
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);
        $data = $request->validate(['option' => 'required|in:full,installment']);
        $payment = $billing->payment($invoice, $data['option']);
        return app(PaymentController::class)->start($payment, $gateway);
    }
}
