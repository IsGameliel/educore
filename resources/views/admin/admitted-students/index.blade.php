@extends('layouts.dash')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title"><span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-account-check"></i></span> Admitted Students</h3>
            <span class="badge badge-gradient-success">{{ number_format($applications->total()) }} records</span>
        </div>
        <p class="text-muted">Students who have completed admission. Open a record to view personal details, academic history and uploaded documents.</p>
        <div class="card mb-4"><div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="GET" action="{{ route('admin.admitted-students.index') }}" class="row g-3">
                <div class="col-md-6 col-xl-4">
                    <label for="search" class="form-label">Search students</label>
                    <input id="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name, email, application or matric number" maxlength="255">
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="department_id" class="form-label">Admission department</label>
                    <select id="department_id" name="department_id" class="form-select">
                        <option value="">All departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="admission_type" class="form-label">Admission type</label>
                    <select id="admission_type" name="admission_type" class="form-select">
                        <option value="">All types</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(request('admission_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label for="entry_year" class="form-label">Entry year</label>
                    <input type="number" id="entry_year" name="entry_year" class="form-control" min="1900" max="9999" value="{{ request('entry_year') }}" placeholder="All years">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-gradient-primary" type="submit">Apply filters</button>
                    <a class="btn btn-light" href="{{ route('admin.admitted-students.index') }}">Reset</a>
                </div>
            </form>
        </div></div>
        <div class="card"><div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Student</th><th>Application / Matric no.</th><th>Department</th><th>Admission</th><th>Completed</th><th>Details</th></tr></thead>
                    <tbody>
                        @forelse ($applications as $application)
                            <tr>
                                <td><strong>{{ $application->first_name }} {{ $application->middle_name }} {{ $application->surname }}</strong><div class="small text-muted mt-1">{{ $application->user?->email ?? '—' }}</div></td>
                                <td>{{ $application->application_number }}<div class="small text-muted mt-1">{{ $application->user?->matric_number ?: 'Matric number not assigned' }}</div></td>
                                <td>{{ $application->department?->name ?? '—' }}<div class="small text-muted mt-1">Entry level: {{ $application->level }}</div></td>
                                <td>{{ $types[$application->admission_type] ?? $application->admission_type }}<div class="small text-muted mt-1">{{ $application->entry_year }}</div></td>
                                <td>{{ $application->completed_at?->format('d M Y') ?? '—' }}</td>
                                <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.admitted-students.show', $application) }}">View details</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">No admitted students found. Try changing your filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $applications->links('pagination::bootstrap-5') }}</div>
        </div></div>
    </div>
</div>
</div>
@endsection
