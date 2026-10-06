@extends('layouts.dash')

@section('content')
@push('styles')
<link rel="stylesheet" href="{{ asset('dash/assets/css/students.css') }}">
@endpush

<div class="main-panel students-page">
    <div class="content-wrapper">

        <div class="page-header">
            <div>
                <span class="students-eyebrow">STUDENT MANAGEMENT</span><h1 class="students-title">Students</h1>
                <p class="small-muted" style="margin-top:4px">Manage student records, departments, and academic levels.</p>
            </div>

            <div class="students-toolbar">
                <a href="{{ route('name-changes.index') }}" class="btn btn-outline-primary btn-sm">Name-change requests</a>
                <a href="{{ route('admin.students.merge.index') }}" class="btn btn-outline-primary btn-sm"><i class="mdi mdi-account-switch"></i> Merge duplicates</a>
                <a href="{{ route('admin.students.create') }}" class="btn brand-btn btn-sm">
                    <i class="mdi mdi-plus me-1"></i> Add New Student
                </a>

                <a href="{{ route('admin.students.import.form') }}" class="btn btn-outline-primary btn-sm">
                    <i class="mdi mdi-upload me-1"></i> Import Students
                </a>

                {{-- Export button: preserves filters and triggers excel export --}}
                <form method="GET" action="{{ route('admin.students.index') }}" id="exportForm">
                    <input type="hidden" name="export" value="excel">
                    <input type="hidden" name="name" value="{{ request('name') }}">
                    <input type="hidden" name="department" value="{{ request('department') }}">
                    <input type="hidden" name="level" value="{{ request('level') }}">
                    <button type="submit" class="btn btn-outline-primary btn-sm" title="Export filtered students to Excel">
                        <i class="mdi mdi-file-excel me-1"></i> Export (Excel)
                    </button>
                </form>
            </div>
        </div>

        <div class="card card-ghost">
            <div class="card-body">

                {{-- Filter form --}}
                <form method="GET" action="{{ route('admin.students.index') }}" class="students-filters">
                    <div class="students-field">
                        <label for="student-name" class="form-label">Student name</label>
                        <input id="student-name" type="search" name="name" value="{{ request('name') }}" class="form-control form-control-sm" placeholder="Enter student name">
                    </div>

                    <div class="students-field">
                        <label for="student-department" class="form-label">Department</label>
                        <select id="student-department" name="department" class="form-select form-select-sm">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="students-field">
                        <label for="student-level" class="form-label">Academic level</label>
                        <select id="student-level" name="level" class="form-select form-select-sm">
                            <option value="">Any Level</option>
                            @foreach(['100','200','300','400','500','600'] as $lvl)
                                <option value="{{ $lvl }}" {{ request('level') == $lvl ? 'selected' : '' }}>{{ $lvl }} Level</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="students-filter-actions">
                        <button type="submit" class="btn brand-btn btn-sm">
                            <i class="mdi mdi-magnify"></i> Search
                        </button>

                        <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary btn-sm">
                            Reset
                        </a>
                    </div>
                </form>

                @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
                @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach</div>@endif
                <form method="POST" action="{{ route('admin.students.bulk-entry-year') }}" class="border rounded p-3 my-3" onsubmit="return confirm('Update the entry year for all {{ $students->total() }} matching students across all pages?')">
                    @csrf
                    @foreach(['name', 'department', 'level'] as $filter)<input type="hidden" name="{{ $filter }}" value="{{ $filters[$filter] ?? '' }}">@endforeach
                    <h2 class="h6">Bulk edit entry year</h2>
                    <p class="small text-muted">Applies to all {{ number_format($students->total()) }} students matching the current filters, across all pages. Apply your filters above before updating.</p>
                    <div class="d-flex flex-wrap align-items-end gap-2">
                        <div><label for="bulk-entry-year" class="form-label">Entry year</label><input id="bulk-entry-year" name="entry_year" type="number" min="1900" max="2100" required value="{{ old('entry_year') }}" class="form-control form-control-sm" placeholder="e.g. 2024"></div>
                        <button type="submit" class="btn brand-btn btn-sm" @disabled($students->total() === 0)>Update matching students</button>
                    </div>
                </form>
                <div class="students-directory-heading"><h2>Student directory</h2><span>{{ number_format($students->total()) }} students</span></div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Matric No.</th>
                                <th>Department</th>
                                <th>Level</th>
                                <th>Entry year</th>
                                <th>Email</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td class="student-name">{{ $student->name }}</td>
                                    <td>{{ $student->matric_number ?: 'Not set' }}</td>
                                    <td>{{ optional($student->department)->name }}</td>
                                    <td><span class="student-level">{{ $student->level }} Level</span></td>
                                    <td>{{ $student->entry_year ?: 'Not set' }}</td>
                                    <td>{{ $student->email }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.students.edit', $student->id) }}" class="btn student-action student-edit" aria-label="Edit {{ $student->name }}" title="Edit student">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.students.destroy', $student->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn student-action student-delete" aria-label="Delete {{ $student->name }}" title="Delete student" onclick="return confirm('Are you sure you want to delete this student?')">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="students-empty"><strong>No students found.</strong><p>Try a different name, department, or level.</p></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="students-pagination"><p>Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ number_format($students->total()) }} students</p>
                    {{ $students->appends(request()->query())->links() }}
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
