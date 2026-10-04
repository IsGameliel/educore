@extends('tuition.layout')
@section('tuition-content')
@foreach($invoices as $invoice) @include('tuition.reminder', ['invoice'=>$invoice]) @endforeach
@if($session)
    <div class="card mb-4"><div class="card-body"><h2 class="h4">{{ $session->name }} registration clearance</h2>
        @foreach($clearances as $semester => $clearance)<p><strong>{{ $semester }} semester:</strong> <span class="{{ $clearance['cleared'] ? 'text-success' : 'text-danger' }}">{{ $clearance['message'] }}</span></p>@endforeach
    </div></div>
@else<div class="alert alert-info">No academic session is active. Tuition will appear when your school publishes fees for your enrollment.</div>@endif
<div class="card"><div class="card-body"><h2 class="h4">Your tuition invoices</h2><div class="table-responsive">
<table class="table"><thead><tr><th>Session / period</th><th>Tuition</th><th>Paid</th><th>Balance</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($invoices as $invoice)
    @php($totals = $invoice->totals())
    <tr><td>{{ $invoice->session_name }} / {{ $invoice->period }}<br><small>{{ $invoice->level }} level · {{ ucfirst($invoice->category) }}</small></td><td>₦{{ number_format($totals['due'] / 100, 2) }}</td><td>₦{{ number_format($totals['paid'] / 100, 2) }}</td><td>₦{{ number_format($totals['balance'] / 100, 2) }}</td><td>{{ ucfirst(str_replace('_', ' ', $totals['status'])) }}@if($invoice->overdue())<br><span class="text-danger">Overdue</span>@endif</td><td><a href="{{ route('tuition.show', $invoice) }}" class="btn btn-outline-primary btn-sm">{{ $totals['balance'] ? 'View & pay' : 'View invoice' }}</a></td></tr>
@empty<tr><td colspan="6">No tuition invoices yet. Contact the bursary if you are ready to register and your fees have not appeared.</td></tr>@endforelse
</tbody></table></div>{{ $invoices->links() }}</div></div></div>
@endsection
