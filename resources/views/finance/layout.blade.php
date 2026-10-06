@extends('layouts.dash')
@section('content')
<div class="main-panel"><div class="content-wrapper">
    <h1 class="h3 mb-4">@yield('heading', 'Tuition administration')</h1>
    <nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Finance navigation">
        <a class="btn btn-outline-primary" href="{{ route('finance.controls') }}">Approvals & settlements</a>
        <a class="btn btn-outline-primary" href="{{ route('finance.payments.export') }}">Export payments Excel</a>
        <a class="btn btn-outline-primary" href="{{ route('finance.templates') }}">Fee templates</a>
        <a class="btn btn-outline-primary" href="{{ route('finance.schedules') }}">Fee schedules</a>
        <a class="btn btn-outline-primary" href="{{ route('finance.invoices') }}">Invoices & balances</a>
        <a class="btn btn-outline-primary" href="{{ route('finance.payments') }}">All payments</a>
        <a class="btn btn-outline-primary" href="{{ route('finance.invoices', ['status'=>'withdrawn']) }}">Withdrawn invoices</a>
        <a class="btn btn-outline-primary" href="{{ route('finance.reminders') }}">Reminder history</a>
    </nav>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach</div>@endif
    @yield('finance-content')
</div></div>
@endsection
