@extends(auth()->user()->dashboardRole() === 'student' ? 'layouts.dash' : 'payments.standalone')

@push('styles')
<style>
    .payment-card { background: #fff; border: 1px solid #e4e8ef; border-radius: 16px; padding: 28px; margin-top: 24px; }
    .payment-amount { font-size: 2.5rem; font-weight: 700; color: #194fb2; }
    .payment-reference { overflow-wrap: anywhere; }
</style>
@endpush

@section('content')
    @if(auth()->user()->dashboardRole() === 'student')
    <div class="main-panel">
        <div class="content-wrapper">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    @if(request()->routeIs('payments.receipts'))
                        <li class="breadcrumb-item active" aria-current="page">Receipts</li>
                    @elseif(request()->routeIs('payments.index'))
                        <li class="breadcrumb-item active" aria-current="page">Payment history</li>
                    @else
                        <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Payment history</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Payment details</li>
                    @endif
                </ol>
            </nav>
        @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach</div>@endif
    @endif

    @yield('payment-content')

    @if(auth()->user()->dashboardRole() === 'student')
        </div>
    </div>
    @endif
@endsection
