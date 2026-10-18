@extends('layouts.dash')
@section('content')
<div class="content-wrapper"><div class="card"><div class="card-body"><h1 class="h3">Payment integration</h1>
<p>Student tuition and service payments go to your institution's own Paystack account. Platform subscription payments are managed separately.</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@foreach($errors->all() as $error)<div class="alert alert-danger">{{ $error }}</div>@endforeach
<p>Status: <strong>{{ $configured ? ucfirst($mode).' account configured' : 'Not configured' }}</strong></p>
<form method="post" action="{{ route('institution.integrations.update') }}">@csrf @method('PUT')
<div class="form-group"><label for="paystack_secret_key">Paystack secret key</label><input class="form-control" id="paystack_secret_key" name="paystack_secret_key" type="password" autocomplete="new-password" required maxlength="210"><small>Use a test key during development. Rotate keys only within the same Paystack business account; changing accounts requires reconciliation of outstanding transactions.</small></div>
<div class="form-group"><label for="current_password">Your administrator password</label><input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required></div><button class="btn btn-primary">Save payment configuration</button></form>
<h2 class="h5 mt-4">Paystack URLs</h2><p>Webhook: <code>{{ route('payments.webhook') }}</code><br>Callback: <code>{{ route('payments.callback') }}</code></p><p>Webhooks need a publicly reachable HTTPS domain. Email uses the platform's configured sender with your institution's name. Shared Firebase integrations are disabled for school portals.</p>
</div></div></div>
@endsection
