@extends('platform.layout')
@section('title', 'Connect your institution address')
@section('content')
<div class="card narrow"><h1>Your branding is saved</h1><p>Your school portal address is <strong>{{ $institution->domains()->where('is_primary', true)->value('domain') }}</strong>.</p><p>Your DNS administrator must point this hostname to the Euvion hosting server. The platform administrator will confirm school authorization, DNS and HTTPS before enabling this address.</p><p>Your payment and setup remain saved. Once approved, return here to enter your school dashboard.</p><a href="{{ route('subscription.onboarding') }}">Check address readiness</a></div>
@endsection
