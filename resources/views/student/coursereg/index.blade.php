@extends('layouts.dash')

@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-home"></i>
                </span> Course Registration
            </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span>Registered Courses
                        <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Registered Courses Display -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('student.courses.registered', ['semester' => $semester]) }}" class="mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="session" class="form-label fw-bold">Academic Session</label>
                            <select name="session" id="session" class="form-control">
                                @foreach($availableSessions as $availableSession)
                                    <option value="{{ $availableSession }}" {{ $session === $availableSession ? 'selected' : '' }}>
                                        {{ $availableSession }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="semester" class="form-label fw-bold">Semester</label>
                            <select name="semester" id="semester" class="form-control">
                                <option value="First" {{ $semester === 'First' ? 'selected' : '' }}>First Semester</option>
                                <option value="Second" {{ $semester === 'Second' ? 'selected' : '' }}>Second Semester</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-gradient-primary w-100">View Courses</button>
                        </div>
                    </div>
                </form>

                <div class="mb-3 d-flex flex-wrap gap-3">
                    <div><strong>Academic Session:</strong> {{ $session }}</div>
                    <div><strong>Semester:</strong> {{ $semester }}</div>
                    <div><strong>Total Courses:</strong> {{ $courses->count() }}</div>
                </div>

                @if(count($courses) === 0)
                    <div class="alert alert-warning">No courses registered for {{ $semester }} Semester in {{ $session }}</div>
                @else
                    <table class="table table-bordered mt-4">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Title</th>
                                <th>Credit Unit</th>
                                <th>Semester</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($courses as $registration)
                                <tr>
                                    <td>{{ $registration->course->code }}</td>
                                    <td>{{ $registration->course->title }}</td>
                                    <td>{{ $registration->course->credit_unit }}</td>
                                    <td>{{ $registration->semester }}</td>
                                    <td>{{ ucfirst($registration->status) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-3">
                        <a href="{{ route('student.courses.download.pdf', ['semester' => $semester, 'session' => $session]) }}" class="btn btn-primary">
                            Download PDF
                        </a>

                        <a href="{{ route('student.courses.download.excel', ['semester' => $semester, 'session' => $session]) }}" class="btn btn-success">
                            Download Excel
                        </a>
                    </div>
                @endif
            </div>
            </div>
        </div>
    </div>
</div>

@endsection
