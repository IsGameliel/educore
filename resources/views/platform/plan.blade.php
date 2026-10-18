@extends('platform.layout')
@section('title', 'Manage plan | Euvion')
@section('content')
<div class="narrow card"><h1>{{ $plan->exists ? 'Edit '.$plan->name : 'Create a plan' }}</h1><p class="muted">Prices are in naira per enrolled student per semester. Promotion applies to the first semester only. Saved invoices retain their original rates and features.</p>
<form method="post" action="{{ $plan->exists ? route('platform.plans.update', $plan->code) : route('platform.plans.store') }}">@csrf @if($plan->exists)@method('PUT')@endif
@unless($plan->exists)<label for="code">Plan code</label><input id="code" name="code" value="{{ old('code') }}" pattern="[a-z][a-z0-9_-]{0,31}" required><small>Lowercase letters, numbers, hyphens, or underscores.</small>@endunless
<label for="name">Plan name</label><input id="name" name="name" value="{{ old('name', $plan->name) }}" required maxlength="255">
<div class="grid"><div><label for="standard_price">Standard price (₦)</label><input id="standard_price" name="standard_price" type="number" min="0.01" max="999999" step="0.01" value="{{ old('standard_price', $plan->exists ? number_format($plan->standard_rate_minor / 100, 2, '.', '') : '') }}" required></div><div><label for="promotional_price">First-semester price (₦)</label><input id="promotional_price" name="promotional_price" type="number" min="0.01" step="0.01" value="{{ old('promotional_price', $plan->exists ? number_format($plan->promotional_rate_minor / 100, 2, '.', '') : '') }}" required></div></div>
<label><input type="checkbox" name="signup_enabled" value="1" @checked(old('signup_enabled', $plan->signup_enabled))>Allow institutions to sign up for this plan</label>
<fieldset><legend>Included features</legend>@foreach($features as $feature)<label><input type="checkbox" name="features[]" value="{{ $feature->code }}" @checked(in_array($feature->code, old('features', $plan->exists ? $plan->features->pluck('code')->all() : [])))>{{ $feature->name }} @if($feature->implementation_status !== 'implemented')<small class="muted">({{ $feature->implementation_status === 'not_found' ? 'Coming soon' : 'Available in part' }})</small>@endif</label>@endforeach</fieldset>
<div class="actions"><button>Save plan</button><a href="{{ route('platform.plans.index') }}">Cancel</a></div></form></div>
@endsection
