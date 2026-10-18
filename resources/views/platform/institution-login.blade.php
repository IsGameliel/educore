@extends('platform.layout')
@section('title', 'Institution account login | Euvion')
@section('content')
<div class="narrow card"><h1>Institution account login</h1><p class="muted">Manage your signup invoice and complete subscription payment. Staff and students use the school portal login.</p>
<form method="post" action="{{ route('subscription.login') }}">@csrf<label for="slug">Institution account ID</label><input id="slug" name="slug" value="{{ old('slug') }}" placeholder="rivers-college" required><label for="email">Administrator’s email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"><div class="actions"><button>Sign in</button></div></form></div>
@endsection
