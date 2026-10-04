@extends('tuition.layout')
@section('heading', 'Tuition invoice')
@section('tuition-content')
@include('tuition.reminder')
@if($invoice->cancelled_at)<div class="alert alert-info">This invoice was cancelled. @if($invoice->replacement_invoice_id)<a href="{{ route('tuition.show', $invoice->replacement_invoice_id) }}">View its replacement invoice.</a>@endif</div>@endif
@php($isActive = $invoice->isActive())
@if(!$isActive && !$invoice->cancelled_at)<div class="alert alert-info">This tuition schedule has been withdrawn. This invoice is retained for your records; further payments are disabled.</div>@endif
@include('tuition.summary')
@include('tuition.gateway-changes')
@if($isActive && $totals['balance'] > 0 && !$totals['refund_review'] && !$totals['reversal_review'])
<div class="card mb-4"><div class="card-body"><h2 class="h4">Pay tuition</h2>
    <p>Already paid? Check the transaction below before starting another payment. An unfinished checkout will be resumed with its original amount.</p>
    <form method="POST" action="{{ route('tuition.pay', $invoice) }}">@csrf
        <label for="payment-option">Payment amount</label><select id="payment-option" name="option" class="form-control mb-3">
            <option value="full">Full outstanding balance — ₦{{ number_format($totals['balance'] / 100, 2) }}</option>
            @if($invoice->period === 'Annual' && $invoice->first_percent < 100 && $invoice->requiredAmount('First') > $totals['paid'])
                <option value="installment">First-semester requirement — ₦{{ number_format(($invoice->requiredAmount('First') - $totals['paid']) / 100, 2) }}</option>
            @endif
        </select><button class="btn btn-primary">Continue to Paystack</button>
    </form>
</div></div>
@endif
<div class="card mb-4"><div class="card-body"><h2 class="h4">Invoice payments</h2><div class="table-responsive"><table class="table"><thead><tr><th>Reference</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($invoice->payments as $payment)<tr><td>{{ $payment->reference }}</td><td>₦{{ number_format($payment->amount / 100, 2) }}</td><td>{{ ucfirst($payment->status) }}</td><td><a href="{{ route('payments.show', $payment) }}">View / check payment</a>@if($payment->status === 'success') · <a href="{{ route('payments.receipt', $payment) }}">Receipt</a>@endif</td></tr>@empty<tr><td colspan="4">No payments yet.</td></tr>@endforelse
</tbody></table></div></div></div></div>
@if($invoice->adjustments->isNotEmpty())<div class="card"><div class="card-body"><h2 class="h4">Adjustments</h2>@foreach($invoice->adjustments as $adjustment)<p>{{ ucfirst($adjustment->type) }}: ₦{{ number_format($adjustment->amount / 100, 2) }} — {{ $adjustment->reason }} ({{ $adjustment->created_at->format('d M Y') }})</p>@endforeach</div></div>@endif
@endsection
