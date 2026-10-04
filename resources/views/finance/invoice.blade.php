@extends('finance.layout')
@section('heading', 'Tuition invoice details')
@section('finance-content')
@include('tuition.summary')
@include('tuition.gateway-changes')
@if(auth()->user()->dashboardRole() === 'bursar' && !$invoice->cancelled_at)
<form method="POST" action="{{ route('finance.invoices.adjust', $invoice) }}" class="card card-body mb-4">@csrf
<h2 class="h4">Request a financial adjustment</h2>
<label>Type<select name="type" class="form-control"><option value="scholarship">Scholarship</option><option value="waiver">Waiver</option></select></label>
<label>Amount (NGN)<input name="amount" type="number" step="0.01" min="0.01" required class="form-control"></label>
<label>Reason<textarea name="reason" minlength="10" maxlength="2000" required class="form-control"></textarea></label>
<button class="btn btn-primary mt-2">Request approval</button>
</form>
@endif
@if($invoice->cancelled_at)
<div class="alert alert-secondary">Cancelled {{ $invoice->cancelled_at->format('d M Y') }}. {{ $invoice->review_reason }}
@if($invoice->replacement_invoice_id)<a href="{{ route('finance.invoices.show', $invoice->replacement_invoice_id) }}">View replacement invoice</a>@else Automatic billing is on hold for this period until an admin explicitly replaces this invoice.@endif</div>
@elseif($invoice->resumed_at)
<div class="alert alert-info">This invoice was resumed after schedule withdrawal. Its original fees, payment policy and transactions still apply. {{ $invoice->review_reason }}</div>
@elseif(!$invoice->isActive())
<div class="alert alert-warning">The fee schedule was withdrawn. Payment is disabled until the original invoice is resumed. {{ $invoice->withdrawal_reviewed_at ? 'Historical record reviewed: '.$invoice->review_reason : 'Admin review is required.' }}</div>
@endif
@if(!$invoice->isActive() && !$invoice->replacement_invoice_id && auth()->user()->dashboardRole() === 'admin')
<form method="POST" action="{{ route('finance.invoices.withdrawal', $invoice) }}" class="card card-body mb-4" onsubmit="return confirm('Apply this withdrawal decision? Review the original invoice and replacement amounts before continuing.');">@csrf
<h2 class="h4">Resolve withdrawn invoice</h2>
<p>Cancellation and replacement require no adjustments or paid charges. Uninitialized attempts are retired; initialized attempts must be freshly verified as failed or abandoned by Paystack. Pending or unavailable gateway transactions block replacement. Otherwise resume the original bill to retain its payment references and balance. A cancelled invoice remains in history and will not be recreated by automatic billing.</p>
<label>Decision<select name="action" class="form-control" required>
@if(!$invoice->cancelled_at)<option value="resume">Resume original invoice for payment</option><option value="retain">Keep fully settled invoice as reviewed history</option><option value="cancel">Cancel untouched invoice and hold further billing</option>@endif
<option value="replace">Cancel and replace using the published schedule below</option>
</select></label>
<label class="mt-3">Replacement schedule (only for replacement)<select name="schedule_id" class="form-control"><option value="">Select when replacing</option>@foreach($replacements as $replacement)<option value="{{ $replacement->id }}">{{ $replacement->period }} — ₦{{ number_format($replacement->amount/100,2) }} — due {{ $replacement->due_date->format('d M Y') }} — {{ $replacement->first_percent }}% initially</option>@endforeach</select></label>
<label class="mt-3">Reason<textarea name="reason" minlength="10" maxlength="2000" required class="form-control"></textarea></label>
<button class="btn btn-primary mt-3">Record decision</button>
</form>
@endif
<div class="card mb-4"><div class="card-body"><h2 class="h4">Registration clearance</h2>
@foreach($registration as $semester=>$state)<p><strong>{{ $semester }} semester:</strong> {{ $state['message'] }}</p>@endforeach
@foreach($clearances as $clearance)<div class="border-top py-3"><p>{{ $clearance->semester }} semester exemption · Expires {{ $clearance->expires_on->format('d M Y') }} · Approved by {{ $clearance->approver->name }} · {{ $clearance->revoked_at ? 'Revoked' : ($clearance->expires_on->lt(today()) ? 'Expired' : 'Active') }}</p><p>{{ $clearance->reason }}</p>
@if(!$clearance->revoked_at && auth()->user()->dashboardRole() === 'admin')<form method="POST" action="{{ route('finance.clearances.revoke', $clearance) }}">@csrf<label>Revocation reason<input name="reason" minlength="10" maxlength="2000" required class="form-control"></label><button class="btn btn-outline-danger btn-sm mt-2">Revoke exemption</button></form>@endif
</div>@endforeach
</div></div>
<div class="card mb-4"><div class="card-body"><h2 class="h4">Payment transactions</h2><div class="table-responsive"><table class="table"><thead><tr><th>Reference</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($invoice->payments as $payment)<tr><td>{{ $payment->reference }}</td><td>₦{{ number_format($payment->amount/100,2) }}</td><td>{{ $payment->status }}</td><td>@if($payment->status === 'success')<a href="{{ route('payments.receipt', $payment) }}">Download receipt</a>@endif<form method="POST" action="{{ route('payments.refresh', $payment) }}">@csrf<button class="btn btn-outline-primary btn-sm">Check Paystack</button></form></td></tr>@empty<tr><td colspan="4">No payments yet.</td></tr>@endforelse
</tbody></table></div></div></div>
<div class="card mb-4"><div class="card-body"><h2 class="h4">Adjustment history</h2>
@forelse($invoice->adjustments as $adjustment)<p><strong>{{ ucfirst($adjustment->type) }} — ₦{{ number_format($adjustment->amount/100,2) }}</strong><br>{{ $adjustment->reason }}<br><small>{{ $adjustment->recorder->name }} · {{ $adjustment->created_at->format('d M Y H:i') }} · {{ $adjustment->external_reference }}</small></p>@empty<p>No adjustments.</p>@endforelse
</div></div>
@if(auth()->user()->dashboardRole() === 'admin' && !$invoice->cancelled_at)
<div class="row"><div class="col-lg-6 mb-4"><form method="POST" action="{{ route('finance.invoices.adjust', $invoice) }}" class="card card-body" onsubmit="return confirm('Record this approved adjustment? It will change the student balance and clearance.');">@csrf
    <h2 class="h4">Request an adjustment for approval</h2>
    <label>Type<select name="type" class="form-control"><option value="scholarship">Scholarship</option><option value="waiver">Fee waiver</option><option value="refund">Completed refund</option></select></label>
    <label class="mt-3">Amount (NGN)<input type="number" step="0.01" min="0.01" max="99999999.99" name="amount" required class="form-control"></label>
    <label class="mt-3">Reason<textarea name="reason" minlength="10" maxlength="2000" required class="form-control"></textarea></label>
    <label class="mt-3">Paystack refund ID (required for refunds)<input name="external_reference" maxlength="255" class="form-control"></label>
    <p class="text-muted mt-2">Refunds must already be completed in Paystack. The ID, invoice and amount are verified before recording; no money is transferred. A refund can reopen the balance. Any extra payment remains visible as a credit balance.</p>
    <label class="mt-3">Match an existing historical refund (optional)<select name="legacy_adjustment_id" class="form-control"><option value="">New refund / already synchronized</option>@foreach($invoice->adjustments->where('type', 'refund')->whereNull('gateway_change_id') as $legacy)<option value="{{ $legacy->id }}">{{ $legacy->external_reference }} / NGN {{ number_format($legacy->amount / 100, 2) }}</option>@endforeach</select></label>
    <button class="btn btn-primary mt-2">Request adjustment</button>
</form></div><div class="col-lg-6 mb-4"><form method="POST" action="{{ route('finance.invoices.exempt', $invoice) }}" class="card card-body">@csrf
    <h2 class="h4">Approve temporary registration exemption</h2><p>The exemption permits registration for one semester while keeping the debt on the invoice.</p>
    <label>Semester<select name="semester" class="form-control"><option>First</option><option>Second</option></select></label>
    <label class="mt-3">Expires on<input name="expires_on" type="date" min="{{ today()->format('Y-m-d') }}" required class="form-control"></label>
    <label class="mt-3">Approval reason<textarea name="reason" minlength="10" maxlength="2000" required class="form-control"></textarea></label>
    <button class="btn btn-primary mt-3">Approve exemption</button>
</form></div></div></div>
@endif
@endsection
