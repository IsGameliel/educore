@extends('finance.layout')
@section('heading', 'Financial approvals & settlements')
@section('finance-content')
<div class="alert alert-info">{{ $pending }} requests await approval. {{ $exceptions }} settlements need reconciliation review.</div>
@if($latePayments->isNotEmpty())
<div class="alert alert-warning"><strong>Payments on cancelled invoices require bursary review.</strong>
@foreach($latePayments as $payment)<div>{{ $payment->reference }} · NGN {{ number_format($payment->amount/100, 2) }} · <a href="{{ route('finance.invoices.show', $payment->tuitionInvoice) }}">{{ $payment->tuitionInvoice->number }}</a></div>@endforeach
</div>
@endif
<h2 class="h4">Adjustment approvals</h2>
@forelse($approvals as $approval)
<div class="card card-body mb-3">
    <a href="{{ route('finance.invoices.show', $approval->invoice) }}">{{ $approval->invoice->number }}</a>
    <p>{{ ucfirst($approval->type) }}: NGN {{ number_format($approval->amount/100, 2) }} · {{ $approval->status }}<br>{{ $approval->reason }}<br>Requested by {{ $approval->requester->name }}</p>
    @if($approval->status === 'pending' && auth()->user()->dashboardRole() === 'admin' && $approval->requested_by !== auth()->id())
    <form method="POST" action="{{ route('finance.approvals.review', $approval) }}">@csrf
        <label>Decision<select name="decision" class="form-control"><option value="approved">Approve</option><option value="rejected">Reject</option></select></label>
        <label>Review reason<textarea name="reason" required minlength="10" maxlength="2000" class="form-control"></textarea></label>
        <button class="btn btn-primary mt-2">Record decision</button>
    </form>
    @elseif($approval->reviewer)<p>Reviewed by {{ $approval->reviewer->name }}: {{ $approval->review_reason }}</p>@endif
</div>
@empty<p>No adjustment requests.</p>@endforelse
{{ $approvals->withQueryString()->links() }}
<h2 class="h4 mt-4">Paystack settlements</h2>
<p>Amounts are gateway payout records. Review exceptions against Paystack and bank statements before treating a payout as reconciled.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>ID / status</th><th>Gross</th><th>Fees</th><th>Net payout</th><th>Matched payments</th><th>Review</th></tr></thead><tbody>
@forelse($settlements as $settlement)<tr>
<td>{{ $settlement->gateway_id }} / {{ $settlement->status }} ({{ $settlement->domain }})</td>
@foreach(['gross','fees','net','matched_amount'] as $field)<td>NGN {{ number_format($settlement->$field/100, 2) }}</td>@endforeach
<td>@forelse($settlement->exceptions as $exception)<div>{{ $exception }}</div>@empty Matched @endforelse<br><small>Checked {{ $settlement->checked_at->format('d M Y H:i') }}</small></td>
</tr>@empty<tr><td colspan="6">No settlements imported.</td></tr>@endforelse
</tbody></table></div>{{ $settlements->withQueryString()->links() }}
</div>
@endsection
