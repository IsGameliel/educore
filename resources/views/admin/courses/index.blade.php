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

            <div class="top-actions d-flex gap-2">
                <a href="{{ route('admin.courses.create') }}" class="btn brand-cta btn-sm"><i class="mdi mdi-plus me-1"></i> Add New Course</a>
                <a href="{{ route('admin.courses.import.form') }}" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-file-import me-1"></i> Import</a>
                <button id="btn-export-excel" class="btn btn-success btn-sm"><i class="mdi mdi-file-excel"></i> Export Excel</button>
            </div>
        </div>

        <div class="card card-eco">
            <div class="card-body">

                {{-- Filter bar --}}
                <form method="GET" action="{{ route('admin.courses.index') }}">
                    <div class="row g-2 mb-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label small">Search by Title</label>
                            <input name="title" value="{{ request('title') }}" type="text" class="form-control form-control-sm" placeholder="Course title">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small">Filter by Department</label>
                            <select name="department" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Departments</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" {{ request('department') == $d->id ? 'selected' : '' }}>
                                        {{ $d->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button class="btn brand-cta btn-sm w-100">Apply</button>
                        </div>
                    </div>
                </form>


                {{-- DataTable --}}
                <div class="table-responsive">
                    <table id="coursesTable" class="table table-striped table-bordered" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Title</th>
                                <th>Credit Unit</th>
                                <th>Semester</th>
                                <th>Department</th>
                                <th style="width:120px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($courses as $course)
                                <tr>
                                    <td class="fw-semibold">{{ $course->code }}</td>
                                    <td>{{ $course->title }}</td>
                                    <td>{{ $course->credit_unit }}</td>
                                    <td>{{ $course->semester }}</td>
                                    <td>{{ optional($course->department)->name }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.courses.edit', $course->id) }}" class="btn btn-sm btn-warning me-1">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.courses.destroy', $course->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?')">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No students found.</td>
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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- Buttons: we won't use excelHtml5 for server-side export; we'll call our server export endpoint -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>

@endsection
