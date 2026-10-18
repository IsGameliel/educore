@extends('platform.layout')
@section('title', 'Semester subscription | Euvion')
@section('content')
<div class="narrow card"><h1>{{ $institution->name }}</h1><h2>{{ $invoice->semester_name }} — {{ $invoice->plan_name }}</h2><p>Invoice <small>{{ $invoice->id }}</small></p>
<table><tr><th>Semester start</th><td>{{ $invoice->semester_start->format('d M Y') }}</td></tr><tr><th>Semester end</th><td>{{ $invoice->semester_end?->format('d M Y') ?? 'Legacy open-ended semester' }}</td></tr><tr><th>Enrolled student count</th><td>{{ number_format($invoice->student_count) }}</td></tr><tr><th>{{ $invoice->promotional ? 'First-semester' : 'Standard' }} rate per student</th><td>₦{{ number_format($invoice->rate_minor / 100, 2) }}</td></tr><tr><th>Total</th><td><strong>₦{{ number_format($invoice->amount_minor / 100, 2) }}</strong></td></tr><tr><th>Payment status</th><td>{{ ucfirst($invoice->status) }}</td></tr></table>
<p class="muted">This fixed semester invoice uses your declared enrollment at semester start. Standard rate for later semesters: ₦{{ number_format($invoice->standard_rate_minor / 100, 2) }} per student. Renewals use standard rates. Student-count and plan changes take effect next semester. No automatic debits or prorated adjustments; contact support for payment corrections. No tax is added by this billing policy.</p>
@if($invoice->status === 'paid')<p class="notice">Payment confirmed. {{ $portalAvailable ? 'Your school portal is available.' : 'Portal access requires an unexpired paid subscription and an active institution.' }}</p>
@if($portalAvailable)
@php($platformParts = parse_url(config('tenancy.platform_url')))
<form method="post" action="{{ route('subscription.portal') }}">@csrf<button>Open school dashboard</button></form><p>Continue securely into your institution's portal.</p>
@endif
@elseif(in_array($institution->status, ['pending','failed','provisioning']))
<p>School setup is {{ $institution->status }}. Confirm your account password to retry setup before payment.</p><form method="post" action="{{ route('subscription.provision') }}">@csrf<label for="password">Institution account password</label><input id="password" name="password" type="password" autocomplete="current-password" required><div class="actions"><button>Retry school setup</button></div></form>
@else<div class="actions"><form method="post" action="{{ route('subscription.checkout') }}">@csrf<button>Proceed to secure payment</button></form><form method="post" action="{{ route('subscription.refresh') }}">@csrf<button class="secondary">Check payment status</button></form></div><p class="muted">Your portal opens after payment is verified. You can sign back into this institution account if checkout is interrupted.</p>@endif
<details><summary>Features included on this invoice</summary><ul>@foreach($invoice->feature_snapshot as $feature)<li>{{ $feature['name'] }}@if($feature['implementation_status'] === 'not_found') — Coming soon @elseif($feature['implementation_status'] === 'partial') — Available in part @endif</li>@endforeach</ul></details></div>
<section class="card narrow"><h2>Semester invoices</h2><table><thead><tr><th>Semester</th><th>Plan</th><th>Total</th><th>Status</th></tr></thead><tbody>@foreach($invoices as $past)<tr><td>{{ $past->semester_name }}<br>{{ $past->semester_start->format('d M Y') }} – {{ $past->semester_end?->format('d M Y') ?? 'Open-ended' }}</td><td>{{ $past->plan_name }}</td><td>₦{{ number_format($past->amount_minor / 100, 2) }}</td><td>{{ $past->status }}</td></tr>@endforeach</tbody></table></section>
@if($invoice->status === 'paid' && $institution->status === 'ready')
<section class="card narrow"><h2>Renew for the next semester</h2><p>Choose your next plan and confirm enrollment. Each semester may span at most 366 days. An existing current package keeps its features until the next semester. If you have no current paid access, verified renewal payment reopens the portal immediately. Unpaid semesters do not grant portal access; your records remain stored.</p>
<form method="post" action="{{ route('subscription.renew') }}">@csrf
<label for="plan_code">Next semester plan</label><select id="plan_code" name="plan_code" required>@foreach($plans as $plan)<option value="{{ $plan->code }}" @selected(old('plan_code', $invoice->plan_code) === $plan->code)>{{ $plan->name }} — ₦{{ number_format($plan->standard_rate_minor / 100, 2) }} / student</option>@endforeach</select>
<label for="semester_name">Semester name</label><input id="semester_name" name="semester_name" value="{{ old('semester_name') }}" maxlength="255" required>
<label for="semester_start">Semester start</label><input id="semester_start" type="date" name="semester_start" value="{{ old('semester_start') }}" required>
<label for="semester_end">Semester end</label><input id="semester_end" type="date" name="semester_end" value="{{ old('semester_end') }}" required>
<label for="student_count">Students enrolled at semester start</label><input id="student_count" name="student_count" type="number" min="1" max="100000" value="{{ old('student_count') }}" required>
<label><input type="checkbox" name="confirm_census" value="1" required>I confirm the semester dates, plan, and enrolled count. This creates a fixed invoice.</label><div class="actions"><button>Create renewal invoice</button></div></form></section>
@endif
@endsection
