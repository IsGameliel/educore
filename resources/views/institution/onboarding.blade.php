@extends('platform.layout')
@section('title', 'Set up your institution | Educore')
@section('content')
<div class="card narrow">
    <p class="badge">Payment verified · School setup</p>
    <h1>Make your school portal yours</h1>
    <p class="muted">Confirm your institution's name, add its logo and choose a brand colour. Next, you'll enter your school dashboard to manage students, staff and the features included in your subscription.</p>
    <form method="post" action="{{ route('subscription.onboarding.store') }}" enctype="multipart/form-data">
        @csrf
        <label for="institution-name">Institution name</label>
        <input id="institution-name" name="name" value="{{ old('name', $institution->name) }}" required maxlength="255" autocomplete="organization">
        <label for="institution-logo">Institution logo</label>
        <input id="institution-logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" @required(empty($institution->settings['logo_path']))>
        <small class="muted">PNG, JPG or WebP, up to 2 MB.</small>
        <label for="brand-colour">Brand colour</label>
        <input id="brand-colour" name="primary_color" type="color" value="{{ old('primary_color', $institution->settings['primary_color'] ?? '#174c85') }}" required style="height:52px;padding:6px">
        <p class="muted">You can change your branding later in Institution Settings.</p>
        <div class="actions"><button>{{ ($institution->settings['domain_pending_verification'] ?? false) ? 'Save branding and check portal address' : 'Save and open my school dashboard' }}</button></div>
    </form>
</div>
@endsection
