@php($feeItems = $payment->feeBreakdown())
<h2 class="h4">Fee breakdown</h2>
<table class="table fee-breakdown"><thead><tr><th>Fee item</th><th>Amount ({{ $payment->currency }})</th></tr></thead><tbody>
    @foreach($feeItems as $item)
        <tr><td>{{ $item['label'] }}</td><td>{{ number_format($item['amount'] / 100, 2) }}</td></tr>
    @endforeach
    <tr><th>{{ $payment->purpose === 'tuition' && $payment->tuitionInvoice ? 'Original invoice fees' : 'Total fees' }}</th><td>{{ number_format(array_sum(array_column($feeItems, 'amount')) / 100, 2) }}</td></tr>
    <tr><th>{{ $payment->status === 'success' ? 'Amount paid in this transaction' : 'Amount payable in this transaction' }}</th><td>{{ number_format($payment->amount / 100, 2) }}</td></tr>
</tbody></table>
@if($payment->purpose === 'tuition' && $payment->tuitionInvoice)
<p class="text-muted">Fee items are from invoice {{ $payment->tuitionInvoice->number }} as issued. This transaction may cover an installment or remaining balance. See the invoice for scholarships, waivers, refunds and the current balance.</p>
@endif
