{{-- resources/views/admin/course_registrations/index.blade.php --}}
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

    .course-reg-page .student-name {
        color: #111827;
        font-weight: 700;
    }

    .course-reg-page .student-meta {
        color: #6b7280;
        font-size: 0.78rem;
        margin-top: 0.15rem;
    }

    .course-reg-page .level-badge {
        background: #eef2ff;
        border-radius: 999px;
        color: #3730a3;
        display: inline-flex;
        font-size: 0.75rem;
        font-weight: 700;
        min-width: 3.2rem;
        padding: 0.35rem 0.7rem;
        justify-content: center;
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
</style>

<div class="main-panel">
    <div class="content-wrapper course-reg-page">
        <div class="page-header">
            <div>
                <h3 class="page-title d-flex align-items-center mb-1">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-clipboard-account"></i>
                    </span>
                    <span class="page-title-text">Course Registrations</span>
                </h3>
                <p class="helper-text mb-0">Manage each student's registered courses per session and semester.</p>
            </div>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.students.index') }}">Students</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Course Registrations</li>
                </ol>
            </nav>
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
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                    <div>
                        <h4 class="card-title mb-1">Students</h4>
                        <p class="helper-text mb-0">Search by name, email, or matric number.</p>
                    </div>

                    <form method="GET" action="{{ route('admin.course-registrations.index') }}" class="d-flex flex-column flex-sm-row gap-2">
                        <select name="session" class="form-control">
                            @foreach($academicSessions as $academicSession)
                                <option value="{{ $academicSession }}" {{ $currentSession === $academicSession ? 'selected' : '' }}>
                                    {{ $academicSession }}
                                </option>
                            @endforeach
                        </select>
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Search name, email, or matric no."
                            class="form-control"
                        >
                        <button class="btn btn-brand" type="submit">
                            <i class="mdi mdi-magnify me-1"></i> Search
                        </button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Level</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td>
                                        <div class="student-name">{{ $student->name }}</div>
                                        <div class="student-meta">{{ $student->matric_number ?? 'No matric number' }}</div>
                                    </td>
                                    <td>{{ $student->email }}</td>
                                    <td>{{ optional($student->department)->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="level-badge">{{ $student->level ?? 'N/A' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a
                                            href="{{ route('admin.course-registrations.show', $student->id) }}?semester=First&session={{ urlencode($currentSession) }}"
                                            class="btn btn-sm btn-brand"
                                        >
                                            <i class="mdi mdi-pencil-box-outline me-1"></i> Manage
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No students found{{ request('q') ? ' for "' . request('q') . '"' : '' }}.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 d-flex justify-content-center">
                    {{ $students->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
