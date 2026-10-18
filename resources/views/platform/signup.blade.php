@extends('platform.layout')
@section('title', 'Register your institution | Euvion')
@section('content')
<div class="narrow card"><h1>Create your institution account</h1><p><span class="badge">{{ $plan->name }}</span> ₦{{ number_format($plan->promotional_rate_minor / 100, 2) }} per student for your first semester. Standard: ₦{{ number_format($plan->standard_rate_minor / 100, 2) }} per student per semester afterward.</p><p class="muted">Create your account, review the semester invoice, then pay securely through Paystack.</p>
<form method="post" action="{{ route('subscription.store', $plan->code) }}">@csrf
<label for="name">Institution name</label><input id="name" name="name" value="{{ old('name') }}" required maxlength="255">
<label for="slug">Institution account ID</label><input id="slug" name="slug" value="{{ old('slug') }}" required maxlength="63" pattern="[a-z0-9]+(-[a-z0-9]+)*"><small>Unique ID for subscription sign-in, such as rivers-college.</small>
<label for="domain">Institution portal address</label><input id="domain" name="domain" value="{{ old('domain') }}" placeholder="portal.rivers-college.gov.ng" required maxlength="253"><small>Use your institution's own hostname, such as academics.mudiameuniversity.edu.ng. Enter no https:// or path. Your DNS administrator must connect it; the platform administrator verifies authorization, DNS and HTTPS before dashboard access. No Euvion subdomain is created.</small>
<label for="owner_name">Administrator’s full name</label><input id="owner_name" name="owner_name" value="{{ old('owner_name') }}" required maxlength="255">
<label for="owner_email">Administrator’s email</label><input id="owner_email" name="owner_email" type="email" value="{{ old('owner_email') }}" required maxlength="255" autocomplete="email">
<label for="password">Password (at least 12 characters)</label><input id="password" name="password" type="password" required minlength="12" maxlength="255" autocomplete="new-password">
<label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="12" autocomplete="new-password">
<div class="grid"><div><label for="semester_name">Semester name</label><input id="semester_name" name="semester_name" value="{{ old('semester_name') }}" placeholder="2026/2027 First Semester" required maxlength="255"></div><div><label for="semester_start">Semester start date</label><input id="semester_start" name="semester_start" type="date" value="{{ old('semester_start') }}" required></div></div>
<label for="semester_end">Semester end date</label><input id="semester_end" name="semester_end" type="date" value="{{ old('semester_end') }}" required>
<p class="muted">Access runs through the semester end date (maximum semester length: 366 days). Renewals use standard rates. Enrollment and plan changes apply next semester; invoices stay fixed. No automatic debits or prorated adjustments. Contact support for payment corrections.</p>
<label for="student_count">Students enrolled at semester start</label><input id="student_count" name="student_count" type="number" min="1" max="100000" value="{{ old('student_count') }}" required>
<label><input type="checkbox" name="confirm_census" value="1" required @checked(old('confirm_census'))>I confirm this is the enrolled student count at semester start. This count is fixed for this invoice.</label>
<details><summary>Included plan features</summary><ul>@foreach($plan->features->where('implementation_status','!=','not_found') as $feature)<li>{{ $feature->name }}@if($feature->implementation_status === 'not_found') — Coming soon @elseif($feature->implementation_status === 'partial') — {{ $feature->notes }} @endif</li>@endforeach</ul></details>
<p class="muted">Deferred features are excluded from this release: {{ $plan->features->where('implementation_status','not_found')->pluck('name')->implode(', ') ?: 'none' }}. No delivery date is promised.</p>
<div class="actions"><button>Create account and review payment</button></div></form><p><a href="{{ route('subscription.login') }}">Already registered? Sign in to your institution account.</a></p></div>
@endsection
