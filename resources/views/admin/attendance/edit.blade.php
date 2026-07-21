@extends('layouts.dash')

@section('content')
@php $brand = '#001F54'; @endphp

<style>
    .brand-btn { background: linear-gradient(90deg, {{ $brand }} 0%, #003366 100%); color:#fff; border:0; }
    .card-ghost { border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,0.06); border:1px solid rgba(0,0,0,0.04); }
    .table thead th { background:linear-gradient(90deg, {{ $brand }} 0%, #003366 100%); color:#fff; }
</style>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-pencil"></i>
                </span>
                Edit Attendance
            </h3>
        </div>

        <div class="card card-ghost">
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route($attendanceRoutePrefix . '.update', $session) }}" data-editing="true">
                    @include('admin.attendance.form', ['method' => 'PUT'])
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
