@extends('layouts.dash')
@section('content')
<div class="main-panel"><div class="content-wrapper">
    <div class="page-header"><h1 class="h3">@yield('heading', 'Tuition & outstanding balance')</h1></div>
    <nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Fees and payments">
        <a href="{{ route('tuition.index') }}" class="btn btn-outline-primary">Tuition & balance</a>
        <a href="{{ route('payments.index') }}" class="btn btn-outline-primary">Payment history</a>
        <a href="{{ route('payments.receipts') }}" class="btn btn-outline-primary">Receipts</a>
    </nav>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach</div>@endif
    @yield('tuition-content')
</div></div>
@endsection
