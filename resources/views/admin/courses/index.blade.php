@extends('layouts.dash')

@section('content')
@php
    $brand = '#001F54';
@endphp

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<style>
/* ----- Branding & layout ----- */
.card-eco { border-radius:12px; border:none; box-shadow:0 6px 20px rgba(3,10,30,0.06); }
.page-title-badge { display:inline-flex; align-items:center; justify-content:center; width:48px; height:48px; border-radius:10px; background: linear-gradient(180deg, {{ $brand }} 0%, #003366 100%); color:#fff; margin-right:12px; }
.brand-cta { background: linear-gradient(90deg, {{ $brand }} 0%, #003B9A 100%); color:#fff; border:none; }
.muted { color:#6b7280; font-size:0.9rem; }

/* Filter dropdown with checkboxes */
.multi-select {
    position: relative;
}
.multi-select .btn-toggle {
    text-align: left;
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .375rem .75rem;
    border-radius: .375rem;
    border:1px solid #d1d5db;
    background:#fff;
}
.multi-select .options {
    position: absolute;
    z-index: 1050;
    background: #fff;
    width: 100%;
    max-height: 220px;
    overflow-y: auto;
    border:1px solid rgba(0,0,0,0.08);
    box-shadow: 0 6px 18px rgba(3,10,30,0.08);
    padding: .5rem;
    border-radius: .5rem;
    margin-top: 6px;
}
.multi-select .option {
    display:flex;
    align-items:center;
    gap:8px;
    padding: .25rem .25rem;
}
.multi-select .option input { transform: scale(1.05); }
.multi-select .option label { margin:0; }

/* Make DataTables buttons smaller and branded */
.dt-buttons .btn {
    border-radius:6px; padding:6px 10px; margin-right:6px;
}
.table thead th { background: linear-gradient(90deg, {{ $brand }} 0%, #003366 100%); color:#fff; }
.course-status-form { min-width: 132px; }
.course-status-select { min-width: 132px; border-color: #d7deea; font-weight: 600; }
.course-status-feedback { display: block; min-height: 15px; margin: 3px 2px 0; color: #16804a; font-size: .68rem; line-height: 1.2; }
.course-status-feedback.is-error { color: #b42318; }

/* Responsive tweaks */
@media (max-width:767px){
    .top-actions { flex-direction: column; gap:8px; }
}
</style>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="d-flex align-items-center">
                <div class="page-title-badge">
                    <i class="mdi mdi-book-open-variant" style="font-size:20px"></i>
                </div>
                <div>
                    <h4 style="margin:0; color:{{ $brand }}; font-weight:700;">Courses</h4>
                    <div class="muted">Manage courses — server-side search & export</div>
                </div>
            </div>

            <div class="top-actions d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('admin.courses.create') }}" class="btn brand-cta btn-sm"><i class="mdi mdi-plus me-1"></i> Add New Course</a>
                <a href="{{ route('admin.courses.export', array_merge(request()->only(['title', 'department_id', 'department', 'academic_session_id']), ['filtered' => 1])) }}" class="btn btn-outline-primary btn-sm"><i class="mdi mdi-file-download-outline me-1"></i> Export filtered</a>
                <a href="{{ route('admin.courses.export') }}" class="btn btn-outline-success btn-sm"><i class="mdi mdi-file-excel me-1"></i> Export all</a>
                <form action="{{ route('admin.courses.import') }}" method="POST" enctype="multipart/form-data" class="d-flex flex-wrap align-items-center gap-2">
                    @csrf
                    <input type="hidden" name="return_to" value="courses">
                    <label for="course-import-file" class="visually-hidden">Choose course file to import</label>
                    <input id="course-import-file" type="file" name="file" class="form-control form-control-sm" accept=".xlsx,.xls,.csv" required style="max-width:220px">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="mdi mdi-file-import me-1"></i> Import</button>
                </form>
            </div>
        </div>

        <div class="card card-eco">
            <div class="card-body">

                {{-- Filter bar --}}
                <form method="GET" action="{{ route('admin.courses.index') }}">
                    <div class="row g-2 mb-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small">Search by Title</label>
                            <input name="title" value="{{ request('title') }}" type="text" class="form-control form-control-sm" placeholder="Course title">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Filter by Department</label>
                            <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Departments</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" {{ request('department_id', request('department')) == $d->id ? 'selected' : '' }}>
                                        {{ $d->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Filter by Session</label>
                            <select name="academic_session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Sessions</option>
                                @foreach($academicSessions as $academicSession)
                                    <option value="{{ $academicSession->id }}" {{ request('academic_session_id') == $academicSession->id ? 'selected' : '' }}>
                                        {{ $academicSession->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 d-flex gap-2">
                            <button class="btn brand-cta btn-sm w-100">Apply</button>
                            <a href="{{ route('admin.courses.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                        </div>
                    </div>
                </form>


                {{-- DataTable --}}
                @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
                @if(session('warning'))<div class="alert alert-warning" role="status">{{ session('warning') }}</div>@endif
                @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                @if(session('import_errors'))
                    <div class="alert alert-warning"><strong>Rows needing attention:</strong>
                        @foreach(session('import_errors') as $errorRow)
                            <div>Row {{ $errorRow['row'] }} ({{ $errorRow['code'] }}): {{ implode(' ', $errorRow['errors']) }}</div>
                        @endforeach
                    </div>
                @endif
                <form id="bulk-course-delete" action="{{ route('admin.courses.bulk-delete') }}" method="POST" class="mb-3">
                    @csrf @method('DELETE')
                    <button id="delete-selected-courses" type="submit" class="btn btn-danger btn-sm" disabled>Delete selected courses (<span id="selected-course-count">0</span>)</button>
                    <span class="text-muted small ms-2">Select courses below. Select all applies to this page only.</span>
                </form>
                <div class="table-responsive">
                    <table id="coursesTable" class="table table-striped table-bordered" style="width:100%;">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all-courses" aria-label="Select all courses on this page"></th>
                                <th>Code</th>
                                <th>Title</th>
                                <th>Credit Unit</th><th>Course Status</th>
                                <th>Session</th>
                                <th>Semester</th>
                                <th>Department</th>
                                <th style="width:120px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($courses as $course)
                                <tr id="course-{{ $course->id }}">
                                    <td><input type="checkbox" name="course_ids[]" value="{{ $course->id }}" form="bulk-course-delete" class="course-selection" aria-label="Select {{ $course->code }} {{ $course->title }}"></td>
                                    <td class="fw-semibold">{{ $course->code }}</td>
                                    <td>{{ $course->title }}</td>
                                    <td>{{ $course->credit_unit }}</td>
                                    <td>
                                        <form class="course-status-form" method="POST" action="{{ route('admin.courses.status.update', $course) }}" data-course-status-form>
                                            @csrf
                                            @method('PATCH')
                                            <label class="visually-hidden" for="course-status-{{ $course->id }}">Status for {{ $course->code }} {{ $course->title }}</label>
                                            <select id="course-status-{{ $course->id }}" name="course_status" class="form-select form-select-sm course-status-select" data-initial-status="{{ $course->course_status }}" aria-describedby="course-status-feedback-{{ $course->id }}">
                                                @foreach(\App\Models\Courses::STATUSES as $status)
                                                    <option value="{{ $status }}" @selected($course->course_status === $status)>{{ $status }}</option>
                                                @endforeach
                                            </select>
                                            <span id="course-status-feedback-{{ $course->id }}" class="course-status-feedback" role="status" aria-live="polite"></span>
                                        </form>
                                    </td>
                                    <td>{{ $course->academicSession?->name ?? 'N/A' }}</td>
                                    <td>{{ $course->semester }}</td>
                                    <td>{{ optional($course->department)->name }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.courses.edit', array_merge(['course' => $course->id], request()->only(['title', 'department_id', 'department', 'academic_session_id', 'page']))) }}" class="btn btn-sm btn-warning me-1">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.courses.destroy', $course->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this course?')">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-3">No courses found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                                {{-- Pagination --}}
                <div class="mt-3 d-flex justify-content-center">
                    {{ $courses->appends(request()->query())->links() }}
                </div>
            </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selections = Array.from(document.querySelectorAll('.course-selection'));
    const selectAll = document.getElementById('select-all-courses');
    const button = document.getElementById('delete-selected-courses');
    function updateSelection() {
        const count = selections.filter(input => input.checked).length;
        document.getElementById('selected-course-count').textContent = count;
        button.disabled = count === 0;
        selectAll.checked = count > 0 && count === selections.length;
        selectAll.indeterminate = count > 0 && count < selections.length;
    }
    selectAll.addEventListener('change', function () {
        selections.forEach(input => { input.checked = selectAll.checked; });
        updateSelection();
    });
    selections.forEach(input => input.addEventListener('change', updateSelection));
    document.querySelectorAll('[data-course-status-form]').forEach(form => {
        const select = form.querySelector('select[name="course_status"]');
        const feedback = form.querySelector('[role="status"]');
        let previousValue = select.value;
        select.addEventListener('change', async function () {
            const payload = new FormData(form);
            select.disabled = true;
            feedback.textContent = 'Saving…';
            feedback.classList.remove('is-error');

            try {
                const response = await fetch(form.action, {
                    // PHP parses multipart form fields reliably for POST; Laravel's
                    // hidden _method field converts this request to PATCH.
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                    },
                    body: payload,
                    credentials: 'same-origin',
                });
                if (!response.ok) {
                    let message = `Save failed (${response.status}).`;
                    try {
                        const problem = await response.json();
                        message = Object.values(problem.errors || {}).flat()[0] || problem.message || message;
                    } catch (_) {
                        // Keep the HTTP status visible when the server returns a non-JSON error page.
                    }
                    throw new Error(message);
                }

                const result = await response.json();
                previousValue = result.course_status;
                select.dataset.initialStatus = previousValue;
                feedback.textContent = 'Saved';
                window.setTimeout(() => { feedback.textContent = ''; }, 1800);
            } catch (error) {
                select.value = previousValue;
                feedback.textContent = error.message || 'Could not save this status. Try again.';
                feedback.classList.add('is-error');
            } finally {
                select.disabled = false;
            }
        });
    });
    document.getElementById('bulk-course-delete').addEventListener('submit', function (event) {
        const count = selections.filter(input => input.checked).length;
        if (!count || !confirm('Delete ' + count + ' selected course(s)? This cannot be undone.')) event.preventDefault();
    });
});
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

@endsection
