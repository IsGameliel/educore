@extends('platform.layout')
@section('title', $title.' | Educore')
@section('content')
<h1>{{ $title }}</h1><section class="card">
@if($section === 'billing')<h2>Subscription billing</h2><p>Current invoices and payment attempts are available on each institution's detail page. A global billing workspace is scheduled for phase 3.</p>
@elseif($section === 'operations')<h2>School operations</h2><p>Current task runs, operational health and lifecycle events are available on each institution's detail page. Central alerts and recovery visibility are scheduled for phase 5.</p>
@else<h2>Platform reporting</h2><p>Overview totals are available today. Date-filtered financial reports and exports are scheduled for phase 6.</p>@endif
<p><a class="button" href="{{ route('platform.institutions.index') }}">Browse institutions</a></p></section>
@endsection
