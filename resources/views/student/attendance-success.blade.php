@extends('layouts.dash')

@section('content')
<div class="main-panel" style="margin-top: 20px;">
    <div class="content-wrapper">
        <div class="card" style="max-width: 720px;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <span class="page-title-icon bg-gradient-primary text-white me-3">
                        <i class="mdi mdi-check-circle"></i>
                    </span>
                    <div>
                        <h3 class="mb-1">Attendance Registered Successfully</h3>
                        <p class="text-muted mb-0">Your attendance has been recorded for this scheduled class.</p>
                    </div>
                </div>

                <div class="border rounded p-3 mb-3">
                    <div><strong>Course:</strong> {{ optional($session->classSchedule->course)->code ?: $session->classSchedule->subject }} {{ optional($session->classSchedule->course)->title }}</div>
                    <div><strong>Date:</strong> {{ $session->attendance_date->format('M d, Y') }}</div>
                    <div><strong>Time:</strong> {{ $session->classSchedule->start_time }} - {{ $session->classSchedule->end_time }}</div>
                    <div><strong>Status:</strong> {{ ucfirst($record->status) }}</div>
                </div>

                <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
            </div>
        </div>
    </div>
</div>
</div>
</div>

@endsection
