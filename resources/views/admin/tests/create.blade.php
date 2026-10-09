@extends('layouts.dash')

@section('content')
@php($routePrefix = auth()->user()->usertype === 'lecturer' ? 'lecturer' : 'admin')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-home"></i>
                </span> Create New Test
            </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span>Create Test <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Create Test Form -->
        <div class="card">
            <div class="card-body">
                <form action="{{ route($routePrefix.'.tests.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Test Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="subject-select" class="form-label">Course</label>
                        <input type="search" id="course-search" class="form-control mb-2" placeholder="Search course code or title" aria-label="Search courses" autocomplete="off" aria-controls="subject-select">
                        <select name="subject" id="subject-select" class="form-control" required>
                            <option value="">Select a course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->title }}" @selected(old('subject') === $course->title)>{{ $course->code }} - {{ $course->title }}</option>
                            @endforeach
                        </select>
                        <small id="course-search-status" class="text-muted" aria-live="polite"></small>
                    </div>
                    <div class="mb-3">
                        <label for="level" class="form-label">Level</label>
                        <select name="level" class="form-control" id="">
                            <option value="100">100</option>
                            <option value="200">200</option>
                            <option value="300">300</option>
                            <option value="400">400</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select name="department_id" id="department_id" class="form-select" required>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="duration" class="form-label">Duration (minutes)</label>
                        <input type="number" name="duration" id="duration" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="1" selected>Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Create Test</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const search = document.getElementById('course-search');
        const select = document.getElementById('subject-select');
        const status = document.getElementById('course-search-status');
        const courses = Array.from(select.options).slice(1);
        const placeholder = select.options[0];
        search.addEventListener('input', function () {
            const term = search.value.trim().toLowerCase();
            const selected = select.selectedOptions[0];
            const matches = courses.filter(option => option.textContent.toLowerCase().includes(term));
            select.replaceChildren(placeholder, ...matches);
            // Retain a chosen course while searching for another one.
            if (selected && selected !== placeholder && !matches.includes(selected)) {
                select.appendChild(selected);
            }
            if (selected) select.value = selected.value;
            placeholder.textContent = matches.length ? 'Select a course' : 'No matching courses';
            status.textContent = `${matches.length} matching courses`;
        });
    });
</script>
@endsection
