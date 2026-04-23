{{-- resources/views/admin/course_registrations/edit.blade.php --}}
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
        white-space: nowrap;
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

    .course-reg-page .status-select {
        color: #111827;
        min-width: 9rem;
    }

    .course-reg-page .tip-text {
        color: #6b7280;
        font-size: 0.75rem;
        margin-top: 0.35rem;
    }
</style>

<div class="main-panel">
    <div class="content-wrapper course-reg-page">
        <div class="page-header">
            <div>
                <h3 class="page-title d-flex align-items-center mb-1">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-pencil-box-outline"></i>
                    </span>
                    <span class="page-title-text">Edit Registration</span>
                </h3>
                <p class="helper-text mb-0">{{ $student->name }} - {{ $semester }} Semester</p>
            </div>

            <a href="{{ route('admin.course-registrations.show', $student->id) }}?semester={{ $semester }}" class="btn btn-outline-secondary btn-sm">
                <i class="mdi mdi-arrow-left me-1"></i> Back
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-2">Please fix these:</div>
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
                            href="{{ route('admin.course-registrations.edit', $student->id) }}?semester=First"
                            class="btn btn-sm semester-link {{ $semester === 'First' ? 'active' : '' }}"
                        >
                            First Semester
                        </a>
                        <a
                            href="{{ route('admin.course-registrations.edit', $student->id) }}?semester=Second"
                            class="btn btn-sm semester-link {{ $semester === 'Second' ? 'active' : '' }}"
                        >
                            Second Semester
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.course-registrations.update', $student->id) }}">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="semester" value="{{ $semester }}">

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Select</th>
                                    <th>Code</th>
                                    <th>Title</th>
                                    <th>Credit Unit</th>
                                    <th>Prerequisites</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($courses as $course)
                                    @php
                                        $checked = in_array($course->id, $registeredCourseIds);
                                        $prereqTitles = $course->prerequisites->pluck('title')->toArray();
                                        $currentStatus = $registeredStatuses[$course->id] ?? 'registered';
                                    @endphp

                                    <tr>
                                        <td>
                                            <input
                                                type="checkbox"
                                                name="course_ids[]"
                                                value="{{ $course->id }}"
                                                {{ $checked ? 'checked' : '' }}
                                                class="form-check-input"
                                            >
                                        </td>

                                        <td class="course-code">{{ $course->code }}</td>
                                        <td>{{ $course->title }}</td>
                                        <td>{{ $course->credit_unit }}</td>

                                        <td>
                                            @if(count($prereqTitles))
                                                <ul class="mb-0 ps-3">
                                                    @foreach($prereqTitles as $title)
                                                        <li>{{ $title }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span class="text-muted">None</span>
                                            @endif
                                        </td>

                                        <td>
                                            <select name="statuses[{{ $course->id }}]" class="form-select form-select-sm status-select">
                                                <option value="registered" {{ $currentStatus === 'registered' ? 'selected' : '' }}>Registered</option>
                                                <option value="pending" {{ $currentStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="approved" {{ $currentStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                                                <option value="rejected" {{ $currentStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                                <option value="withdrawn" {{ $currentStatus === 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                                            </select>

                                            <div class="tip-text">Only applies if course is selected.</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No courses available for this student's department, level, and semester.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 d-flex flex-column flex-sm-row align-items-sm-center justify-content-end gap-2">
                        <a href="{{ route('admin.course-registrations.show', $student->id) }}?semester={{ $semester }}"
                           class="btn btn-outline-secondary">
                            Cancel
                        </a>

                        <button type="submit" class="btn btn-success">
                            <i class="mdi mdi-content-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
