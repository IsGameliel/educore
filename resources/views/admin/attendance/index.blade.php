@extends('layouts.dash')

@section('content')
@php
    $brand = '#001F54';
    $attendanceRoutePrefix = $attendanceRoutePrefix ?? 'admin.attendance';

    $scheduleLabel = function ($schedule) {
        $course = $schedule->course ? "{$schedule->course->code} - {$schedule->course->title}" : $schedule->subject;
        $department = optional($schedule->department)->name ?: 'No department';
        return "{$department} | {$schedule->level} Level | {$course} | {$schedule->day}";
    };
@endphp

<style>
    .attendance-header { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:1rem; }
    .brand-btn { background: linear-gradient(90deg, {{ $brand }} 0%, #003366 100%); color:#fff; border:0; }
    .card-ghost { border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,0.06); border:1px solid rgba(0,0,0,0.04); }
    .filter-bar { background:#f8fafc; padding:14px; border-radius:10px; border:1px solid rgba(0,0,0,0.04); }
    .table thead th { background:linear-gradient(90deg, {{ $brand }} 0%, #003366 100%); color:#fff; vertical-align:middle; }
    .small-muted { font-size:0.85rem; color:#6b7280; }
    .metric-pill { display:inline-flex; align-items:center; min-width:64px; justify-content:center; padding:0.25rem 0.5rem; border-radius:6px; font-weight:700; }
</style>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="attendance-header">
            <div>
                <h3 class="page-title d-flex align-items-center mb-1">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-clipboard-check"></i>
                    </span>
                    <span style="font-weight:600; color:{{ $brand }}">Attendance</span>
                </h3>
                <p class="small-muted mb-0">Daily class attendance reports by class and date</p>
            </div>
            <a href="{{ route($attendanceRoutePrefix . '.create') }}" class="btn brand-btn btn-sm">
                <i class="mdi mdi-plus me-1"></i> Take Attendance
            </a>
        </div>

        <div class="card card-ghost">
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form method="GET" action="{{ route($attendanceRoutePrefix . '.index') }}" class="row g-3 mb-4 filter-bar align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Class</label>
                        <select name="class_schedule_id" class="form-select form-select-sm">
                            <option value="">All Classes</option>
                            @foreach($classSchedules as $schedule)
                                <option value="{{ $schedule->id }}" {{ request('class_schedule_id') == $schedule->id ? 'selected' : '' }}>
                                    {{ $scheduleLabel($schedule) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Exact Date</label>
                        <input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From</label>
                        <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To</label>
                        <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn brand-btn btn-sm w-100">
                            <i class="mdi mdi-filter"></i> Filter
                        </button>
                        <a href="{{ route($attendanceRoutePrefix . '.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Class</th>
                                <th>Present</th>
                                <th>Late</th>
                                <th>Absent</th>
                                <th>Excused</th>
                                <th>Taken By</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sessions as $session)
                                <tr>
                                    <td class="fw-semibold">{{ $session->attendance_date->format('M d, Y') }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($session->classSchedule->course)->code ?: $session->classSchedule->subject }}</div>
                                        <div class="small-muted">{{ optional($session->classSchedule->department)->name }} | {{ $session->classSchedule->level }} Level | {{ $session->classSchedule->day }}</div>
                                    </td>
                                    <td><span class="metric-pill bg-success text-white">{{ $session->present_count }}</span></td>
                                    <td><span class="metric-pill bg-warning text-dark">{{ $session->late_count }}</span></td>
                                    <td><span class="metric-pill bg-danger text-white">{{ $session->absent_count }}</span></td>
                                    <td><span class="metric-pill bg-info text-white">{{ $session->excused_count }}</span></td>
                                    <td>{{ optional($session->takenBy)->name ?: 'Not recorded' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route($attendanceRoutePrefix . '.show', $session) }}" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <a href="{{ route($attendanceRoutePrefix . '.edit', $session) }}" class="btn btn-sm btn-warning me-1">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        <form action="{{ route($attendanceRoutePrefix . '.destroy', $session) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this attendance session?')">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No attendance sessions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-center">
                    {{ $sessions->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
@endsection
