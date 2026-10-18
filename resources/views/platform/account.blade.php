@extends('platform.layout')
@section('title', 'My Account | Educore')
@section('content')
<h1>My Account</h1><p>{{ $admin->name }} · {{ $admin->email }}</p>
<p><a class="button secondary" href="{{ route('platform.confirm.form') }}">Confirm password for sensitive actions</a></p>
<section class="card"><h2>Change password</h2><form method="post" action="{{ route('platform.account.password') }}">@csrf
<label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
<label>New password<input type="password" name="password" required minlength="12" autocomplete="new-password"></label><small>At least 12 characters, with uppercase and lowercase letters and a number.</small>
<label>Confirm new password<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
<div class="actions"><button>Change password and revoke other sessions</button></div></form></section>
<section class="card"><h2>Two-factor authentication</h2>
@if($recoveryCodes)<div class="notice"><strong>Save these recovery codes now. Each works once.</strong><ul>@foreach($recoveryCodes as $code)<li><code>{{ $code }}</code></li>@endforeach</ul></div>@endif
@if($admin->two_factor_confirmed_at)
<p>Enabled. Your authenticator is required when signing in.</p>
<form method="post" action="{{ route('platform.account.two-factor.disable') }}">@csrf @method('DELETE')
<label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label><label>Authenticator or recovery code<input name="code" required autocomplete="one-time-code"></label><div class="actions"><button class="danger">Disable two-factor authentication</button></div></form>
@else
<p>Add a time-based account to your authenticator using the setup key below.</p>
@if($setupSecret)
<p>Account: {{ $admin->email }}<br>Issuer: Educore Platform<br>Setup key: <code>{{ $setupSecret }}</code><br>Type: time-based, 6 digits, 30 seconds. Setup expires after ten minutes.</p>
<form method="post" action="{{ route('platform.account.two-factor.enable') }}">@csrf
<label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label><label>Authenticator code<input name="code" required inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code"></label><div class="actions"><button>Confirm and enable</button></div></form>
@else
<form method="post" action="{{ route('platform.account.two-factor.setup') }}">@csrf
<label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label><div class="actions"><button>Start authenticator setup</button></div></form>
@endif
@endif</section>
<section class="card"><h2>Signed-in sessions</h2><p>Expired browser sessions may remain listed until revoked. Revocation blocks the next request from that session.</p>
<div class="table-wrap"><table><thead><tr><th>Device</th><th>Address / last seen</th><th>Action</th></tr></thead><tbody>
@forelse($sessions as $session)<tr><td>{{ $session->user_agent ?: 'Unknown device' }}</td><td>{{ $session->ip_address }}<br>{{ $session->last_seen_at }}</td><td>
@if($session->id === $currentKey)This session
@else<form method="post" action="{{ route('platform.account.sessions.revoke', $session->id) }}">@csrf @method('DELETE')<label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label><button>Revoke session</button></form>@endif
</td></tr>@empty<tr><td colspan="3">No sessions recorded.</td></tr>@endforelse
</tbody></table></div></section>
@endsection
