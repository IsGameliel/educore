@extends('payments.layout')
@section('title', 'Payment details')
@section('payment-content')
    @php($withdrawnTuition = $payment->purpose === 'tuition' && ! \App\Models\TuitionInvoice::active()->whereKey($payment->tuition_invoice_id)->exists())
    <a href="{{ $payment->destination() }}">&larr; Back to {{ $payment->purpose === 'tuition' ? 'tuition invoice' : ($payment->purpose === 'application' ? 'admission' : 'request') }}</a>
    <section class="payment-card">
        <p class="text-muted">{{ \App\Models\Payment::LABELS[$payment->purpose] }}</p>
        <h1 class="h3">{{ $payment->status === 'success' ? 'Payment confirmed' : ($withdrawnTuition ? 'Tuition schedule withdrawn' : 'Complete your payment') }}</h1>
        <p class="payment-amount">&#8358;{{ number_format($payment->amount / 100, 2) }}</p>
        <dl class="row">
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ ucfirst($payment->status) }}</dd>
            <dt class="col-sm-3">Reference</dt><dd class="col-sm-9 payment-reference">{{ $payment->reference }}</dd>
            <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ $payment->email }}</dd>
            <dt class="col-sm-3">Created</dt><dd class="col-sm-9">{{ $payment->created_at->format('d M Y, H:i') }}</dd>
            @if($payment->paid_at)<dt class="col-sm-3">Paid</dt><dd class="col-sm-9">{{ $payment->paid_at->format('d M Y, H:i') }}</dd>@endif
        </dl>
        <div class="table-responsive mb-3">@include('payments.fee-breakdown')</div>
        @if($payment->status === 'success')
            @if($payment->gateway_deduction || $payment->dispute_open || $payment->gateway_reversed)
                <div class="alert alert-warning">This is the original successful transaction. Verified refunds/reversals: NGN {{ number_format($payment->gateway_deduction / 100, 2) }}. {{ $payment->dispute_open ? 'A dispute is under review.' : '' }} Your tuition invoice shows the current balance and clearance.</div>
            @endif
            @if($payment->purpose === 'tuition')<form method="POST" action="{{ route('payments.refresh', $payment) }}">@csrf<button class="btn btn-outline-primary">Check refunds and disputes</button></form>@endif
            <p>{{ $payment->purpose === 'tuition' ? 'Your payment has been verified and credited to your tuition invoice.' : 'Your payment has been verified and your request submitted.' }}</p>
            <a href="{{ route('payments.receipt', $payment) }}" class="btn btn-outline-primary">Download receipt</a>
            <a href="{{ $payment->destination() }}" class="btn btn-primary">Continue</a>
        @elseif($withdrawnTuition)
            <p>This tuition schedule has been withdrawn. Further payments are disabled. You can still check a payment already made.</p>
            <form method="POST" action="{{ route('payments.refresh', $payment) }}">@csrf<button type="submit" class="btn btn-outline-primary">Check payment status</button></form>
        @elseif($payment->purpose === 'tuition' && $payment->payable->status === 'superseded')
            <p>This payment attempt has been replaced. Continue from your tuition invoice.</p>
            <a class="btn btn-primary" href="{{ $payment->destination() }}">Return to tuition invoice</a>
        @else
            <p>Your details are saved. {{ $payment->purpose === 'tuition' ? 'Your tuition balance and registration clearance will update after payment is confirmed.' : ($payment->purpose === 'application' ? 'Your admission will be completed after payment is confirmed.' : 'Your request will be submitted for review after payment is confirmed.') }}</p>
            <p class="text-muted">Already paid? Check payment status before trying again.</p>
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('payments.checkout', $payment) }}">@csrf<button type="submit" class="btn btn-primary">Pay &#8358;{{ number_format($payment->amount / 100) }} with Paystack</button></form>
                <form method="POST" action="{{ route('payments.refresh', $payment) }}">@csrf<button type="submit" class="btn btn-outline-primary">Check payment status</button></form>
            </div></div>
        @endif
    </section>
    </div>
@endsection
