@php($gatewayChanges = \App\Models\PaymentGatewayChange::whereIn('payment_id', $invoice->payments->modelKeys())->latest()->get())
@if($gatewayChanges->isNotEmpty())
<div class="card mb-4"><div class="card-body"><h2 class="h4">Refunds and disputes</h2>
<p>These updates have been verified with Paystack. Pending refunds do not reduce your payments. An unresolved dispute requires bursary review.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>Type / ID</th><th>Amount</th><th>Status</th><th>Resolution</th></tr></thead><tbody>
@foreach($gatewayChanges as $change)<tr><td>{{ ucfirst($change->kind) }} / {{ $change->gateway_id }}</td><td>NGN {{ number_format($change->amount / 100, 2) }}</td><td>{{ $change->status }}</td><td>{{ $change->resolution ?? '—' }}</td></tr>@endforeach
</tbody></table></div></div></div>
@endif
