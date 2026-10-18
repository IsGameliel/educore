@extends('platform.layout')
@section('title', 'Plans | Euvion')
@section('content')
<h1>Plans</h1>
<p class="muted">Manage subscription packages, semester pricing and included features.</p>
<section class="card">
    <div class="actions" style="margin-top:0;margin-bottom:24px"><a class="button" href="{{ route('platform.plans.create') }}">Create plan</a></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Plan</th><th>Standard / student</th><th>First semester / student</th><th>Features</th><th>Signup</th><th>Action</th></tr></thead>
        <tbody>@forelse($plans as $plan)
            <tr><td>{{ $plan->name }}</td><td>₦{{ number_format($plan->standard_rate_minor / 100, 2) }}</td><td>₦{{ number_format($plan->promotional_rate_minor / 100, 2) }}</td><td>{{ $plan->features_count }}</td><td>{{ $plan->signup_enabled ? 'Open' : 'Closed' }}</td><td><a href="{{ route('platform.plans.edit', $plan->code) }}">Edit</a></td></tr>
        @empty<tr><td colspan="6">No plans yet. Create your first subscription plan.</td></tr>@endforelse</tbody>
    </table></div>
</section>
@endsection
