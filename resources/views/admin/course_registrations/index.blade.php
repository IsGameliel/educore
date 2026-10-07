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
                        <select name="department_id" class="form-control" aria-label="Department">
                            <option value="">All departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                        <select name="current_level" class="form-control" aria-label="Current level">
                            <option value="">All current levels</option>
                            @foreach(['100','200','300','400','500','600'] as $level)
                                <option value="{{ $level }}" @selected(request('current_level') === $level)>Current {{ $level }} Level</option>
                            @endforeach
                        </select>
                        <select name="session" class="form-control">
                            <option value="all" @selected($currentSession === 'all')>All sessions</option>
                            <option value="unassigned" @selected($currentSession === 'unassigned')>Session not assigned</option>
                            @foreach($academicSessions as $academicSession)
                                @continue(in_array($academicSession, ['all', 'unassigned'], true))
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

                <div class="border rounded p-3 mb-3">
                    <h5>Bulk session level update</h5>
                    <p class="helper-text">Choose a department above and click Search. Filter by current level to select the right cohort. This saves historical session levels without changing current profile levels.</p>
                    <form id="bulk-session-level" method="POST" action="{{ route('admin.course-registrations.bulk-session-level') }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="department_id" value="{{ request('department_id') }}">
                        <input type="hidden" name="current_level" value="{{ request('current_level') }}">
                        <input type="hidden" name="q" value="{{ request('q') }}">
                        <div class="d-flex flex-wrap gap-3 align-items-end">
                            <div>
                                <label for="bulk-target-session">Session to update</label>
                                <select id="bulk-target-session" name="target_session" class="form-control" required>
                                    <option value="">Select session</option>
                                    @foreach($academicSessions as $academicSession)
                                        @continue(in_array($academicSession, ['all', 'unassigned'], true))
                                        <option value="{{ $academicSession }}" @selected(old('target_session', $currentSession) === $academicSession)>{{ $academicSession }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="bulk-target-level">Level for that session</label>
                                <select id="bulk-target-level" name="level" class="form-control" required>
                                    <option value="">Select level</option>
                                    @foreach(['100','200','300','400','500','600'] as $level)
                                        <option value="{{ $level }}" @selected(old('level') === $level)>{{ $level }} Level</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="bulk-scope">Students to update</label>
                                <select id="bulk-scope" name="scope" class="form-control">
                                    <option value="selected" @selected(old('scope', 'selected') === 'selected')>Ticked students on this page</option>
                                    <option value="all_matching" @selected(old('scope') === 'all_matching')>All {{ $students->total() }} students matching filters (every page)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-brand" @disabled(!request('department_id'))>Save bulk session level</button>
                        </div>
                        <p class="helper-text mt-2 mb-0">Every selected student receives the same session level. If existing results conflict, the entire update stops and shows the affected students.</p>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-page-students" aria-label="Select all students on this page"></th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Current level</th>
                                <th>Session level</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td><input type="checkbox" class="bulk-student" name="student_ids[]" value="{{ $student->id }}" form="bulk-session-level" aria-label="Select {{ $student->name }}" @checked(in_array($student->id, old('student_ids', [])))></td>
                                    <td>
                                        <div class="student-name">{{ $student->name }}</div>
                                        <div class="student-meta">{{ $student->matric_number ?? 'No matric number' }}</div>
                                    </td>
                                    <td>{{ $student->email }}</td>
                                    <td>{{ optional($student->department)->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="level-badge">{{ $student->level ?? 'N/A' }}</span>
                                    </td>
                                    <td>{{ $sessionLevels[$student->id][$currentSession] ?? (in_array($currentSession, ['all', 'unassigned'], true) ? 'Select a session' : 'Not set') }}</td>
                                    <td class="text-end">
                                        <a
                                            href="{{ route('admin.course-registrations.show', $student->id) }}?semester=First&session={{ urlencode($currentSession) }}"
                                            class="btn btn-sm btn-brand"
                                        >
                                            <i class="mdi mdi-pencil-box-outline me-1"></i> Courses &amp; Results
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
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
</div>
<script>
    const pageSelection = document.getElementById('select-page-students');
    const studentSelections = Array.from(document.querySelectorAll('.bulk-student'));
    pageSelection.addEventListener('change', () => {
        studentSelections.forEach(input => input.checked = pageSelection.checked);
        pageSelection.indeterminate = false;
    });
    studentSelections.forEach(input => input.addEventListener('change', () => {
        const count = studentSelections.filter(input => input.checked).length;
        pageSelection.checked = count > 0 && count === studentSelections.length;
        pageSelection.indeterminate = count > 0 && count < studentSelections.length;
    }));
</script>
@endsection
