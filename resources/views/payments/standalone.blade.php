<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Payments') | Euvion</title>
    <link rel="stylesheet" href="{{ asset('dash/assets/vendors/css/vendor.bundle.base.css') }}">
    <link rel="stylesheet" href="{{ asset('dash/assets/css/style.css') }}">
    <style>body{background:#f5f7fb;color:#18253b}.payment-shell{max-width:1080px;margin:0 auto;padding:32px 20px}.payment-nav{background:#fff;border-bottom:1px solid #e4e8ef;padding:18px 24px}.payment-card{background:#fff;border:1px solid #e4e8ef;border-radius:16px;padding:28px;margin-top:24px}.payment-amount{font-size:2.5rem;font-weight:700;color:#194fb2} .payment-reference{overflow-wrap:anywhere} label{display:block} a:focus,button:focus{outline:2px solid #194fb2;outline-offset:3px}</style>
</head>
<body>
    <nav class="payment-nav d-flex flex-wrap align-items-center justify-content-between gap-3" aria-label="Payments navigation">
        <a href="{{ route('dashboard') }}" class="fw-bold">Euvion</a>
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('payments.index') }}">My payments</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="btn btn-outline-secondary btn-sm">Logout</button></form>
        </div>
    </nav>
    <main class="payment-shell">
        @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach</div>@endif
        @yield('content')
    </main>
</body>
</html>
