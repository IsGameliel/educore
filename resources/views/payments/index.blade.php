@extends('payments.layout')
@section('payment-content')
    <h1 class="h3">My payments</h1>
    <p class="text-muted">Track your fees, resume checkout, or confirm a payment.</p>
    <div class="payment-card table-responsive">
        <table class="table"><thead><tr><th>Fee</th><th>Amount</th><th>Status</th><th>Date</th><th>Details</th></tr></thead><tbody>
        @forelse($payments as $payment)
            <tr><td>{{ \App\Models\Payment::LABELS[$payment->purpose] }}</td><td>&#8358;{{ number_format($payment->amount / 100, 2) }}</td><td>{{ ucfirst($payment->status) }}</td><td>{{ $payment->created_at->format('d M Y') }}</td><td><a href="{{ route('payments.show', $payment) }}">{{ $payment->status === 'success' ? 'View payment' : 'Continue payment' }}</a></td></tr>
        @empty<tr><td colspan="5">No payments yet. Tuition payments and application, transcript, and result appeal fees will appear here.</td></tr>@endforelse
        </tbody></table>
        {{ $payments->links() }}
    </div></div>
@endsection
