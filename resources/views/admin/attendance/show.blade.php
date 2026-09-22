@extends('layouts.dash')

@section('content')
@php
    $brand = '#001F54';
    $attendanceRoutePrefix = $attendanceRoutePrefix ?? 'admin.attendance';
    $counts = [
        'present' => $summary->get('present', 0),
        'late' => $summary->get('late', 0),
        'absent' => $summary->get('absent', 0),
        'excused' => $summary->get('excused', 0),
    ];
@endphp

<style>
    .brand-btn { background: linear-gradient(90deg, {{ $brand }} 0%, #003366 100%); color:#fff; border:0; }
    .card-ghost { border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,0.06); border:1px solid rgba(0,0,0,0.04); }
    .table thead th { background:linear-gradient(90deg, {{ $brand }} 0%, #003366 100%); color:#fff; }
    .small-muted { font-size:0.85rem; color:#6b7280; }
    .summary-card { border:1px solid rgba(0,0,0,0.06); border-radius:8px; padding:12px; background:#fff; }
    .scan-panel { border:1px solid rgba(0,0,0,0.06); border-radius:8px; padding:16px; background:#f8fafc; }
    .scan-url { word-break:break-all; font-size:0.85rem; }
</style>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <div>
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-clipboard-check"></i>
                    </span>
                    Attendance Report
                </h3>
                <p class="small-muted mb-0">{{ $session->attendance_date->format('M d, Y') }} | {{ optional($session->classSchedule->department)->name }} | {{ $session->classSchedule->level }} Level</p>
            </div>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route($attendanceRoutePrefix . '.scan-code.send', $session) }}">
                    @csrf
                    <button type="submit" class="btn brand-btn btn-sm">
                        <i class="mdi mdi-qrcode"></i> {{ $session->scan_token ? 'Resend Code' : 'Generate Code' }}
                    </button>
                </form>
                <a href="{{ route($attendanceRoutePrefix . '.edit', $session) }}" class="btn btn-warning btn-sm">
                    <i class="mdi mdi-pencil"></i> Edit
                </a>
                <a href="{{ route($attendanceRoutePrefix . '.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-3 mb-4">
            @foreach($counts as $label => $count)
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="small-muted text-uppercase">{{ $label }}</div>
                        <div class="h4 mb-0">{{ $count }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card card-ghost mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="small-muted">Course</div>
                        <div class="fw-semibold">{{ optional($session->classSchedule->course)->code ?: $session->classSchedule->subject }} {{ optional($session->classSchedule->course)->title }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small-muted">Schedule</div>
                        <div class="fw-semibold">{{ $session->classSchedule->day }} | {{ $session->classSchedule->start_time }} - {{ $session->classSchedule->end_time }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small-muted">Taken By</div>
                        <div class="fw-semibold">{{ optional($session->takenBy)->name ?: 'Not recorded' }}</div>
                    </div>
                    @if($session->notes)
                        <div class="col-12">
                            <div class="small-muted">Notes</div>
                            <div>{{ $session->notes }}</div>
                        </div>
                    @endif
                    <div class="col-12">
                        <div class="scan-panel">
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                @if($scanUrl)
                                    @php
                                        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($scanUrl);
                                    @endphp
                                    <img src="{{ $qrUrl }}" alt="Attendance scan code" width="140" height="140">
                                    <div>
                                        <div class="small-muted">Scan Code Link</div>
                                        <div class="scan-url mb-2">{{ $scanUrl }}</div>
                                        <div class="small-muted">
                                            Sent: {{ optional($session->scan_code_sent_at)->format('M d, Y g:i A') ?: 'Not sent' }}
                                            @if($session->scan_expires_at)
                                                | Expires: {{ $session->scan_expires_at->format('M d, Y g:i A') }}
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div>
                                        <div class="fw-semibold">No scan code generated yet.</div>
                                        <div class="small-muted">Use Generate Code to email students a QR code for this attendance session.</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-ghost">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Matric No.</th>
                                <th>Status</th>
                                <th>Registered At</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($session->records->sortBy(fn($record) => optional($record->student)->name ?? '') as $record)
                                <tr>
                                    <td class="fw-semibold">{{ optional($record->student)->name }}</td>
                                    <td>{{ optional($record->student)->matric_number ?: 'Not set' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $record->status === 'present' ? 'success' : ($record->status === 'late' ? 'warning text-dark' : ($record->status === 'absent' ? 'danger' : 'info')) }}">
                                            {{ ucfirst($record->status) }}
                                        </span>
                                    </td>
                                    <td>{{ optional($record->registered_at)->format('M d, Y g:i A') ?: '-' }}</td>
                                    <td>{{ $record->reason ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No records found for this session.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
@endsection
