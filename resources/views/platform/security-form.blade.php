@extends('platform.layout')
@section('title', 'Account security | Educore')
@section('content')
<div class="card narrow">
@if($mode === 'confirm')
<h1>Confirm your password</h1><p>Confirmation lasts ten minutes. Return to your previous page afterward to repeat the action.</p>
<form method="post" action="{{ route('platform.confirm') }}">@csrf
<label for="current_password">Current password</label><input id="current_password" type="password" name="current_password" required autocomplete="current-password"><button>Confirm password</button>
@elseif($mode === 'challenge')
<h1>Two-factor authentication</h1><p>Enter the code from your authenticator or an unused recovery code. This sign-in request expires after five minutes.</p>
<form method="post" action="{{ route('platform.challenge') }}">@csrf
<label for="code">Authenticator or recovery code</label><input id="code" name="code" required autocomplete="one-time-code" maxlength="100"><button>Verify and sign in</button>
@elseif($mode === 'forgot')
<h1>Recover platform access</h1><p>We will send a password recovery link if your account is active.</p>
<form method="post" action="{{ route('platform.forgot.send') }}">@csrf
<label for="email">Platform email</label><input id="email" type="email" name="email" required autocomplete="username"><button>Send recovery link</button>
@else
<h1>Set your platform password</h1>
<form method="post" action="{{ route('platform.reset') }}">@csrf
<input type="hidden" name="token" value="{{ $token }}"><input type="hidden" name="email" value="{{ $email }}">
<label for="password">New password</label><input id="password" type="password" name="password" required minlength="12" autocomplete="new-password"><small>At least 12 characters, with uppercase and lowercase letters and a number.</small>
<label for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"><button>Save password</button>
@endif
</form><p><a href="{{ route('platform.login') }}">Back to sign in</a></p></div>
@endsection
