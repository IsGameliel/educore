@extends('layouts.dash')

@section('content')
@php
    // primary color
    $brand = '#001F54';
@endphp

<style>
    /* Simple themed styles */
    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom: 1rem;
    }

    .brand-btn {
        background: linear-gradient(90deg, {{ $brand }} 0%, #003366 100%);
        color: #fff;
        border: none;
    }

    .card-ghost {
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.06);
        border: 1px solid rgba(0,0,0,0.04);
    }

    .filter-bar {
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        padding: 14px;
        border-radius: 10px;
        border: 1px solid rgba(0,0,0,0.04);
    }

    .table thead th {
        background: linear-gradient(90deg, {{ $brand }} 0%, #003366 100%);
        color: #fff;
        vertical-align: middle;
    }

    .export-btn {
        background: #0b233f;
        color: #fff;
    }

    .small-muted { font-size: 0.85rem; color: #6b7280; }
</style>

<div class="main-panel">
    <div class="content-wrapper">

        <div class="page-header">
            <div>
                <h3 class="page-title d-flex align-items-center">
                    <span class="page-title-icon bg-gradient-primary text-white me-2" style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:8px;">
                        <i class="mdi mdi-school" style="font-size:20px"></i>
                    </span>
                    <span style="margin-left:12px; font-weight:600; color:{{ $brand }}">Students</span>
                </h3>
                <p class="small-muted" style="margin-top:4px">Manage and export student records</p>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.students.create') }}" class="btn brand-btn btn-sm">
                    <i class="mdi mdi-plus me-1"></i> Add New Student
                </a>

                {{-- Export button: preserves filters and triggers excel export --}}
                <form method="GET" action="{{ route('admin.students.index') }}" id="exportForm">
                    <input type="hidden" name="export" value="excel">
                    <input type="hidden" name="name" value="{{ request('name') }}">
                    <input type="hidden" name="department" value="{{ request('department') }}">
                    <input type="hidden" name="level" value="{{ request('level') }}">
                    <button type="submit" class="btn export-btn btn-sm" title="Export filtered students to Excel">
                        <i class="mdi mdi-file-excel me-1"></i> Export (Excel)
                    </button>
                </form>
            </div>
        </div>

        <div class="card card-ghost">
            <div class="card-body">

                {{-- Filter form --}}
                <form method="GET" action="{{ route('admin.students.index') }}" class="row g-3 mb-4 filter-bar align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search by Name</label>
                        <input type="text" name="name" value="{{ request('name') }}" class="form-control form-control-sm" placeholder="Enter student name">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select form-select-sm">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Level</label>
                        <select name="level" class="form-select form-select-sm">
                            <option value="">Any Level</option>
                            @foreach(['100','200','300','400','500','600'] as $lvl)
                                <option value="{{ $lvl }}" {{ request('level') == $lvl ? 'selected' : '' }}>{{ $lvl }} Level</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn brand-btn btn-sm w-100">
                            <i class="mdi mdi-magnify"></i> Search
                        </button>

                        <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                            Reset
                        </a>
                    </div>
                </form>

                {{-- Table --}}
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Level</th>
                                <th>Email</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td class="fw-semibold">{{ $student->name }}</td>
                                    <td>{{ optional($student->department)->name }}</td>
                                    <td>{{ $student->level }} Level</td>
                                    <td>{{ $student->email }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.students.edit', $student->id) }}" class="btn btn-sm btn-warning me-1">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.students.destroy', $student->id) }}" method="POST" class="d-inline">
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
                    {{ $students->appends(request()->query())->links() }}
                </div>

            </div>
        </div>
    </div>
    </div>
</div>
@endsection
