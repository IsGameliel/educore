@extends('layouts.dash')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h1 class="page-title">{{ auth()->user()->role_name }} Dashboard</h1>
        </div>
        <div class="row">
            <div class="col-12 grid-margin stretch-card">
                <div class="card"><div class="card-body">
                    <h2 class="card-title">Welcome, {{ auth()->user()->name }}</h2>
                    <p class="text-muted mb-0">Manage your tasks, projects, and account from your staff dashboard.</p>
                </div></div>
            </div>
        </div>
        @include('dashboard.widgets')
    </div>
</div>
</div>
@endsection
