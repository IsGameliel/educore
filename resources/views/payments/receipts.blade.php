@extends('payments.layout')
@section('title', 'Payment receipts')
@section('payment-content')
<h1 class="h3">Payment receipts</h1><p>Download receipts for verified payments.</p>
<div class="payment-card table-responsive"><table class="table"><thead><tr><th>Receipt</th><th>Fee</th><th>Amount</th><th>Paid</th><th>Download</th></tr></thead><tbody>
@forelse($payments as $payment)<tr><td>{{ $payment->receiptNumber() }}</td><td>{{ \App\Models\Payment::LABELS[$payment->purpose] }}</td><td>₦{{ number_format($payment->amount/100,2) }}</td><td>{{ $payment->paid_at?->format('d M Y H:i') }}</td><td><a href="{{ route('payments.receipt', $payment) }}">Download PDF</a></td></tr>@empty<tr><td colspan="5">No receipts yet. Receipts appear after payment is verified.</td></tr>@endforelse
</tbody></table>{{ $payments->links() }}</div></div>
@endsection
