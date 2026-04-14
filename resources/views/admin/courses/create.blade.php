@extends('layouts.dash')

@push('styles')
    <style>
        .department-picker {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            overflow: hidden;
        }

        .department-picker__search {
            border: 0;
            border-bottom: 1px solid #eef2f7;
            border-radius: 0;
            box-shadow: none;
            padding: 0.9rem 1rem;
        }

        .department-picker__search:focus {
            box-shadow: none;
            border-color: #eef2f7;
        }

        .department-picker__list {
            max-height: 280px;
            overflow-y: auto;
            padding: 0.5rem;
        }

        .department-option {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 0.85rem;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .department-option:hover {
            background: #f8f4ff;
        }

        .department-option input {
            margin: 0;
        }

        .department-option__text {
            color: #374151;
            font-weight: 500;
        }

        .department-picker__actions {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-top: 1px solid #eef2f7;
            background: #fafbff;
        }

        .department-picker__empty {
            padding: 0.85rem;
            color: #6b7280;
            text-align: center;
        }
    </style>
@endpush

@section('content')

    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-home"></i>
                    </span> Create Course
                </h3>
                <nav aria-label="breadcrumb">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            <span></span>Create Course <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Registration Form -->
            <div class="card">
                <div class="card-body">

                    <form action="{{ route('admin.courses.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="code">Course Code</label>
                            <input type="text" name="code" id="code" class="form-control" value="{{ old('code') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="credit_unit">Credit Unit</label>
                            <input type="number" name="credit_unit" id="credit_unit" class="form-control" value="{{ old('credit_unit') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="semester">Semester</label>
                            <select name="semester" id="semester" class="form-control" required>
                                <option value="">Choose Semester</option>
                                <option value="First">First</option>
                                <option value="Second">Second</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="semester">Level</label>
                            <select name="level" id="level" class="form-control" required>
                                <option value="">Choose level</option>
                                <option value="100">100 Level</option>
                                <option value="200">200 Level</option>
                                <option value="300">300 Level</option>
                                <option value="400">400 Level</option>
                                <option value="500">500 Level</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="department_ids">Departments</label>
                            <div class="department-picker">
                                <input
                                    type="text"
                                    class="form-control department-picker__search"
                                    id="department-search-create"
                                    placeholder="Search departments"
                                    autocomplete="off"
                                >
                                <div class="department-picker__list" id="department-list-create">
                                    @foreach($departments as $department)
                                        <label class="department-option" data-department-name="{{ strtolower($department->name) }}">
                                            <input
                                                type="checkbox"
                                                name="department_ids[]"
                                                value="{{ $department->id }}"
                                                {{ in_array($department->id, old('department_ids', [])) ? 'checked' : '' }}
                                            >
                                            <span class="department-option__text">{{ $department->name }}</span>
                                        </label>
                                    @endforeach
                                    <div class="department-picker__empty d-none">No matching departments found.</div>
                                </div>
                                <div class="department-picker__actions">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-select-all="#department-list-create">
                                        Select All
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-clear-all="#department-list-create">
                                        Clear
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">One course record will be created for each checked department.</small>
                        </div>
                        <button type="submit" class="btn btn-success">Save</button>
                    </form>
                </div>
            </div>
        </div>
        </div>
    </div>

@endsection

@section('scripts')
    @include('admin.courses._department_picker_script')
@endsection
