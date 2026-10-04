@extends('layouts.dash')
@section('content')
<div class="main-panel"><div class="content-wrapper">
    <div class="page-header"><div><h1 class="h3">Payments</h1><p class="text-muted mb-0">Monitor tuition, application, transcript and result appeal fees.</p></div></div>
    <div class="d-flex flex-wrap gap-2 mb-3"><a href="{{ route('finance.schedules') }}" class="btn btn-outline-primary">Tuition fee schedules</a><a href="{{ route('finance.invoices') }}" class="btn btn-outline-primary">Tuition invoices & balances</a></div>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach</div>@endif
    @unless(config('services.paystack.secret_key'))<div class="alert alert-warning">Paystack is not configured. Add the Paystack secret key to enable checkout.</div>@endunless
    <div class="row">
        @foreach(['collected' => 'Collected (NGN)', 'successful' => 'Successful payments', 'pending' => 'Awaiting payment', 'unsuccessful' => 'Failed / abandoned / reversed'] as $key => $label)
        <div class="col-md-6 col-xl-3 mb-3"><div class="card h-100"><div class="card-body"><p class="text-muted">{{ $label }}</p><h2 class="h3">{{ $key === 'collected' ? '₦'.number_format($stats[$key] / 100, 2) : number_format($stats[$key]) }}</h2></div></div></div>
        @endforeach
    </div>
    <p class="text-muted small">Totals reflect the filters below. Dates filter when the payment record was created.</p>
    <form method="GET" class="card card-body mb-4">
        <div class="row g-3">
            <div class="col-md-4"><label for="payment-search">Name, email or reference</label><input id="payment-search" class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="255"></div>
            <div class="col-md-4"><label for="payment-purpose">Fee type</label><select id="payment-purpose" name="purpose" class="form-control"><option value="">All fees</option>@foreach(\App\Models\Payment::LABELS as $value => $label)<option value="{{ $value }}" @selected(($filters['purpose'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label for="payment-status">Status</label><select id="payment-status" name="status" class="form-control"><option value="">All statuses</option>@foreach(['pending','success','failed','abandoned','reversed'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <div class="col-md-4"><label for="payment-from">From</label><input id="payment-from" name="from" type="date" class="form-control" value="{{ $filters['from'] ?? '' }}"></div>
            <div class="col-md-4"><label for="payment-to">To</label><input id="payment-to" name="to" type="date" class="form-control" value="{{ $filters['to'] ?? '' }}"></div>
            <div class="col-md-4 d-flex align-items-end gap-2"><button type="submit" class="btn btn-primary">Filter payments</button><a class="btn btn-outline-secondary" href="{{ route(auth()->user()->dashboardRole() === 'admin' ? 'admin.payments.index' : 'finance.payments') }}">Reset</a></div>
        </div>
    </form>
    <div class="card"><div class="card-body"><div class="table-responsive">
        <table class="table"><thead><tr><th>Payer</th><th>Fee / request</th><th>Amount</th><th>Status</th><th>Reference</th><th>Dates</th><th>Action</th></tr></thead><tbody>
        @forelse($payments as $payment)
        <tr>
            <td>{{ $payment->user->name }}<br><small>{{ $payment->email }}</small></td>
            <td>{{ \App\Models\Payment::LABELS[$payment->purpose] }}<br><small>Request #{{ $payment->payable_id }}</small></td>
            <td>&#8358;{{ number_format($payment->amount / 100, 2) }}</td>
            <td><span class="badge {{ $payment->status === 'success' ? 'bg-success' : ($payment->status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}">{{ ucfirst($payment->status) }}</span>@if($payment->channel)<br><small>{{ $payment->channel }}</small>@endif</td>
            <td style="white-space:normal;min-width:180px;overflow-wrap:anywhere">{{ $payment->reference }}@if($payment->gateway_id)<br><small>Paystack ID: {{ $payment->gateway_id }}</small>@endif</td>
            <td><small>Created: {{ $payment->created_at->format('d M Y H:i') }}@if($payment->paid_at)<br>Paid: {{ $payment->paid_at->format('d M Y H:i') }}@endif</small></td>
            <td>@if($payment->status !== 'success')<form method="POST" action="{{ route('payments.refresh', $payment) }}">@csrf<button class="btn btn-outline-primary btn-sm" type="submit">Check Paystack</button></form>@else<span class="text-success">Verified</span>@endif</td>
        </tr>
        @empty<tr><td colspan="7">No payments match these filters.</td></tr>@endforelse
        </tbody></table>
    </div><div class="mt-3">{{ $payments->links() }}</div></div></div>
</div></div>
</div>
@endsection
