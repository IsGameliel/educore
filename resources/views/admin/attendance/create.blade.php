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
                    <i class="mdi mdi-clipboard-plus"></i>
                </span>
                Take Attendance
            </h3>
        </div>

        <div class="card card-ghost">
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route($attendanceRoutePrefix . '.store') }}">
                    @include('admin.attendance.form')
                </form>
            </div>
        </div>

        <div class="card card-ghost mt-4">
            <div class="card-body">
                <h4 class="card-title mb-2">Generate Scan Code</h4>
                <p class="text-muted mb-3">Email a scanable attendance code to students in the selected class department and level. Students who scan it will be marked present after login.</p>
                <form method="POST" action="{{ route($attendanceRoutePrefix . '.scan-code.store') }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">Class</label>
                        <select name="class_schedule_id" class="form-select">
                            <option value="">Select Class</option>
                            @foreach($classSchedules as $schedule)
                                @php
                                    $course = $schedule->course ? "{$schedule->course->code} - {$schedule->course->title}" : $schedule->subject;
                                    $department = optional($schedule->department)->name ?: 'No department';
                                @endphp
                                <option value="{{ $schedule->id }}" {{ old('class_schedule_id', $session->class_schedule_id) == $schedule->id ? 'selected' : '' }}>
                                    {{ $department }} | {{ $schedule->level }} Level | {{ $course }} | {{ $schedule->day }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="attendance_date" value="{{ old('attendance_date', is_object($session->attendance_date) && method_exists($session->attendance_date, 'format') ? $session->attendance_date->format('Y-m-d') : $session->attendance_date) }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn brand-btn w-100">
                            <i class="mdi mdi-qrcode"></i> Generate & Email
                        </button>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control" placeholder="Optional class note">{{ old('notes', $session->notes) }}</textarea>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
