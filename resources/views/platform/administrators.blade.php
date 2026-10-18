@extends('platform.layout')
@section('title', 'Administrators | Educore')
@section('content')
<h1>Administrators</h1><p>Owners manage administrators and configuration. Finance handles billing and reports. Support handles school operations. Read-only accounts cannot change records.</p>
<p><a href="{{ route('platform.confirm.form') }}">Confirm password before making changes</a></p>
<section class="card"><h2>Invite administrator</h2><form method="post" action="{{ route('platform.administrators.invite') }}">@csrf
<div class="grid"><label>Name<input name="name" required maxlength="255" value="{{ old('name') }}"></label><label>Email<input type="email" name="email" required maxlength="255" value="{{ old('email') }}"></label>
<label>Role<select name="role">@foreach(\App\Models\Central\PlatformAdministrator::ROLES as $value => $label)<option value="{{ $value }}" @selected($value === old('role', 'read_only'))>{{ $label }}</option>@endforeach</select></label></div>
<label>Your current password<input type="password" name="current_password" required autocomplete="current-password"></label><div class="actions"><button>Send invitation</button></div></form></section>
@foreach($administrators as $administrator)
<section class="card"><h2>{{ $administrator->name }}</h2><p>{{ $administrator->email }} · {{ $administrator->is_active ? 'Active' : 'Inactive' }} · Two-factor {{ $administrator->two_factor_confirmed_at ? 'enabled' : 'not enabled' }}</p>
<form method="post" action="{{ route('platform.administrators.update', $administrator->id) }}">@csrf @method('PATCH')
<div class="grid"><label>Role<select name="role">@foreach(\App\Models\Central\PlatformAdministrator::ROLES as $value => $label)<option value="{{ $value }}" @selected($administrator->role === $value)>{{ $label }}</option>@endforeach</select></label><label>Status<select name="is_active"><option value="1" @selected($administrator->is_active)>Active</option><option value="0" @selected(! $administrator->is_active)>Inactive</option></select></label></div>
<label>Reason<input name="reason" required minlength="10" maxlength="2000"></label><label>Your current password<input type="password" name="current_password" required autocomplete="current-password"></label><div class="actions"><button>Update administrator</button></div></form>
@if($administrator->is_active)<details><summary>Send a new password setup/recovery link</summary><form method="post" action="{{ route('platform.administrators.resend', $administrator->id) }}">@csrf<label>Your current password<input type="password" name="current_password" required autocomplete="current-password"></label><div class="actions"><button>Send recovery link</button></div></form></details>@endif
</section>@endforeach
{{ $administrators->links() }}
@endsection
