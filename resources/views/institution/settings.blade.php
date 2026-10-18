@extends('layouts.dash')
@section('content')
<div class="content-wrapper"><div class="card"><div class="card-body"><h1 class="h3">Institution settings</h1><p>Manage your institution's identity and contact details.</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@foreach($errors->all() as $error)<div class="alert alert-danger">{{ $error }}</div>@endforeach
<form method="post" action="{{ route('institution.settings.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
<div class="form-group"><label>Institution name</label><input class="form-control" name="name" value="{{ old('name', $institution->name) }}" required></div>
<div class="form-group"><label>Timezone</label><select class="form-control" name="timezone">@foreach(timezone_identifiers_list() as $timezone)<option value="{{ $timezone }}" @selected(old('timezone', $institution->timezone) === $timezone)>{{ $timezone }}</option>@endforeach</select></div>
<div class="form-group"><label>Contact email</label><input class="form-control" type="email" name="contact_email" value="{{ old('contact_email', $institution->settings['contact_email'] ?? '') }}"></div>
<div class="form-group"><label>Contact phone</label><input class="form-control" name="contact_phone" value="{{ old('contact_phone', $institution->settings['contact_phone'] ?? '') }}"></div>
<div class="form-group"><label>Brand color</label><input class="form-control" type="color" name="primary_color" value="{{ old('primary_color', $institution->settings['primary_color'] ?? '#174c85') }}"></div>
<div class="form-group"><label>Institution logo</label><input class="form-control" type="file" name="logo" accept="image/png,image/jpeg,image/webp"></div>
<button class="btn btn-primary">Save settings</button></form></div></div></div>
@endsection
