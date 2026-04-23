{{-- resources/views/admin/course_registrations/show.blade.php --}}
@extends('layouts.dash')

@section('content')
<style>
    .course-reg-page .page-title-text {
        color: #001f54;
        font-weight: 700;
    }

    .course-reg-page .helper-text {
        color: #6b7280;
        font-size: 0.875rem;
    }

    .course-reg-page .card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 12px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
    }

    .course-reg-page .student-chip {
        background: #f3f4f6;
        border-radius: 999px;
        color: #374151;
        display: inline-flex;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 0.4rem 0.75rem;
    }

    .course-reg-page .credit-summary {
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        border-radius: 10px;
        color: #312e81;
        display: inline-flex;
        gap: 0.5rem;
        padding: 0.7rem 1rem;
    }

    .course-reg-page .table thead th {
        background: #001f54;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        vertical-align: middle;
        white-space: nowrap;
    }

    .course-reg-page .table tbody td {
        color: #1f2937;
        vertical-align: middle;
    }

    .course-reg-page .course-code {
        color: #111827;
        font-weight: 700;
    }

    .course-reg-page .status-badge {
        background: #f3f4f6;
        border-radius: 999px;
        color: #374151;
        display: inline-flex;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.35rem 0.7rem;
        text-transform: capitalize;
    }

    .course-reg-page .btn-brand {
        background: #001f54;
        border-color: #001f54;
        color: #fff;
    }

    .course-reg-page .btn-brand:hover {
        background: #003366;
        border-color: #003366;
        color: #fff;
    }

    .course-reg-page .semester-link {
        border: 1px solid #d1d5db;
        color: #374151;
        font-weight: 600;
    }

    .course-reg-page .semester-link:hover {
        background: #f9fafb;
        color: #111827;
    }

    .course-reg-page .semester-link.active {
        background: #001f54;
        border-color: #001f54;
        color: #fff;
    }
</style>

<div class="main-panel">
    <div class="content-wrapper course-reg-page">
        <div class="page-header">
            <div>
                <h3 class="page-title d-flex align-items-center mb-1">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-clipboard-account"></i>
                    </span>
                    <span class="page-title-text">Registered Courses</span>
                </h3>
                <p class="helper-text mb-0">{{ $student->name }} - {{ $semester }} Semester</p>
            </div>

            <a href="{{ route('admin.course-registrations.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="mdi mdi-arrow-left me-1"></i> Back to Students
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-xl-row align-items-xl-start justify-content-between gap-4 mb-4">
                    <div>
                        <div class="helper-text text-uppercase fw-bold mb-1">Student</div>
                        <h4 class="mb-1">{{ $student->name }}</h4>
                        <div class="helper-text mb-3">{{ $student->email }}</div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="student-chip">Dept: {{ optional($student->department)->name ?? 'N/A' }}</span>
                            <span class="student-chip">Level: {{ $student->level ?? 'N/A' }}</span>
                            <span class="student-chip">Matric: {{ $student->matric_number ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a
                            href="{{ route('admin.course-registrations.show', $student->id) }}?semester=First"
                            class="btn btn-sm semester-link {{ $semester === 'First' ? 'active' : '' }}"
                        >
                            First Semester
                        </a>
                        <a
                            href="{{ route('admin.course-registrations.show', $student->id) }}?semester=Second"
                            class="btn btn-sm semester-link {{ $semester === 'Second' ? 'active' : '' }}"
                        >
                            Second Semester
                        </a>
                        <a
                            href="{{ route('admin.course-registrations.edit', $student->id) }}?semester={{ $semester }}"
                            class="btn btn-sm btn-success"
                        >
                            <i class="mdi mdi-pencil me-1"></i> Edit Courses
                        </a>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="credit-summary">
                        <span>Total Credit Units:</span>
                        <strong>{{ $totalCredits }}</strong>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Title</th>
                                <th>Credit Unit</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($registrations as $reg)
                                <tr>
                                    <td class="course-code">{{ $reg->course->code ?? 'N/A' }}</td>
                                    <td>{{ $reg->course->title ?? 'N/A' }}</td>
                                    <td>{{ $reg->course->credit_unit ?? 0 }}</td>
                                    <td>
                                        <span class="status-badge">{{ $reg->status ?? 'registered' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No registered courses for {{ $semester }} Semester.
                                    </td>
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
