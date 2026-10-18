@extends('layouts.dash')
@section('content')
<div class="content-wrapper"><div class="card"><div class="card-body"><h1 class="h3">School exports and restore points</h1>
<p>Exports contain this institution's records and uploaded files. They expire after {{ config('tenancy.exports.retention_days') }} days. Whole-platform database backups are managed by the platform operator.</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@foreach($errors->all() as $error)<div class="alert alert-danger">{{ $error }}</div>@endforeach
<form method="post" action="{{ route('institution.exports.store') }}">@csrf<label for="export-password">Administrator password</label><input id="export-password" class="form-control" type="password" name="current_password" autocomplete="current-password" required><button class="btn btn-primary mt-2">Create school export</button></form>
@foreach($exports as $export)<section class="border rounded p-3 mt-3"><h2 class="h6">{{ $export->created_at->format('d M Y H:i') }}</h2><p><code>{{ $export->id }}</code><br>Status: {{ $export->status }} · Expires {{ $export->expires_at->format('d M Y H:i') }}</p>
@if($export->status==='ready' && $export->expires_at->isFuture())
<a class="btn btn-outline-primary" href="{{ route('institution.exports.download',$export->id) }}">Download school export</a>
<details class="mt-3"><summary>Restore this point</summary><p>Restoring replaces this school's records and files with this point. A current safety export is saved first. Current account passwords, security settings and deactivation states are retained; sessions and API tokens are revoked. Financial changes after this point block self-service restore and require operator-assisted recovery.</p><form method="post" action="{{ route('institution.exports.restore',$export->id) }}">@csrf
<label>Administrator password</label><input class="form-control" type="password" name="current_password" autocomplete="current-password" required>
<label>Type RESTORE {{ $export->id }}</label><input class="form-control" name="confirmation" autocomplete="off" required>
<label><input type="checkbox" name="acknowledge" value="1" required> I understand this restores this school only and signs everyone out.</label><div><button class="btn btn-danger mt-2">Restore school</button></div></form></details>
<details class="mt-3"><summary>Remove this export</summary><form method="post" action="{{ route('institution.exports.destroy',$export->id) }}">@csrf @method('DELETE')<label>Administrator password</label><input class="form-control" type="password" name="current_password" autocomplete="current-password" required><label>Type DELETE {{ $export->id }}</label><input class="form-control" name="confirmation" autocomplete="off" required><button class="btn btn-outline-danger mt-2">Remove export archive</button></form></details>
@endif</section>@endforeach
</div></div></div>
@endsection
