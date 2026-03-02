@extends('layouts.dash')

@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-home"></i>
                </span> Students Results Management
            </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span>View Students Results <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="container">
                    <h2>Manage Student Results</h2>

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" action="{{ route('admin.results.index') }}">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-2">
                                        <label for="department_id" class="form-label fw-bold">Department</label>
                                        <select name="department_id" id="department_id" class="form-select">
                                            <option value="">All Departments</option>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                                    {{ $department->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="user_id" class="form-label fw-bold">Student</label>
                                        <select name="user_id" id="user_id" class="form-select">
                                            <option value="">All Students</option>
                                            @foreach ($students as $student)
                                                <option value="{{ $student->id }}" {{ request('user_id') == $student->id ? 'selected' : '' }}>
                                                    {{ $student->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="session" class="form-label fw-bold">Session</label>
                                        <input type="text" name="session" id="session" class="form-control" placeholder="e.g., 2023/2024" value="{{ request('session') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="semester" class="form-label fw-bold">Semester</label>
                                        <select name="semester" id="semester" class="form-select">
                                            <option value="">All Semesters</option>
                                            <option value="First" {{ request('semester') == 'First' ? 'selected' : '' }}>First</option>
                                            <option value="Second" {{ request('semester') == 'Second' ? 'selected' : '' }}>Second</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="level" class="form-label fw-bold">Level</label>
                                        <input type="text" name="level" id="level" class="form-control" placeholder="e.g., 200" value="{{ request('level') }}">
                                    </div>
                                    <div class="col-md-2 d-grid">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                    </div>
                                </div>
                            </form>
                            <form method="GET" action="{{ route('admin.results.export') }}" class="mt-3">
                                <div class="row">
                                    <div class="col-md-2 offset-md-10 d-grid">
                                        <input type="hidden" name="department_id" value="{{ request('department_id') }}">
                                        <input type="hidden" name="user_id" value="{{ request('user_id') }}">
                                        <input type="hidden" name="session" value="{{ request('session') }}">
                                        <input type="hidden" name="semester" value="{{ request('semester') }}">
                                        <input type="hidden" name="level" value="{{ request('level') }}">
                                        <button type="submit" class="btn btn-success">Export to Excel</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Export to Excel Button (separate form) -->
                    <form method="GET" action="{{ route('admin.results.export') }}" class="mb-3">
                        <input type="hidden" name="department_id" value="{{ request('department_id') }}">
                        <input type="hidden" name="user_id" value="{{ request('user_id') }}">
                        <input type="hidden" name="session" value="{{ request('session') }}">
                        <input type="hidden" name="semester" value="{{ request('semester') }}">
                        <input type="hidden" name="level" value="{{ request('level') }}">
                        <button type="submit" class="btn btn-success">Export to Excel</button>
                    </form>
                    <!-- Actions -->
                    <div class="mb-3">
                        <a href="{{ route('admin.results.create') }}" class="btn btn-primary">Add Result</a>
                        <a href="{{ route('admin.results.upload') }}" class="btn btn-primary">Upload Results</a>

                        @if(request('session') && request('semester'))
                            <form method="POST" action="{{ route('admin.results.transcripts.bulk', [request('session'), request('semester')]) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success">Generate All Transcripts for {{ request('session') }} {{ request('semester') }}</button>
                            </form>
                        @endif
                    </div>

                    <!-- Responsive Results Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Department</th>
                                    <th>Matric Number</th>
                                    <th>Session</th>
                                    <th>Semester</th>
                                    <th>Level</th>
                                    <th>Courses</th>
                                    <th>Transcript</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $renderedFullTranscriptButtons = [];
                                    $groupedResults = $results->groupBy(function($item) {
                                        return $item->user_id . '_' . $item->department_id . '_' . $item->session . '_' . $item->semester;
                                    });
                                @endphp

                                @foreach ($groupedResults as $group)
                                    @php
                                        $first = $group->first();
                                        $fullTranscriptKey = $first->user_id . '_' . $first->department_id;
                                    @endphp
                                    <tr>
                                        <td>{{ $first->user->name }}</td>
                                        <td>{{ $first->department->name ?? 'N/A' }}</td>
                                        <td>{{ $first->matric_number }}</td>
                                        <td>{{ $first->session }}</td>
                                        <td>{{ $first->semester }}</td>
                                        <td>{{ $first->level }}</td>
                                        <td>
                                            <ul class="mb-0">
                                                @foreach ($group as $result)
                                                    <li>{{ $result->course_code }}: {{ $result->course_title }} ({{ $result->score }} / {{ $result->grade }})</li>
                                                @endforeach
                                            </ul>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.results.transcript.generate', [$first->user_id, urlencode($first->session), $first->semester]) }}">
                                                @csrf
                                                <input type="hidden" name="department_id" value="{{ $first->department_id }}">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    {{ $first->transcript_path ? 'Regenerate' : 'Generate' }}
                                                </button>
                                            </form>

                                            @if($first->transcript_path)
                                                <a href="{{ asset(ltrim($first->transcript_path, '/')) }}" 
                                                    target="_blank" 
                                                    class="btn btn-sm btn-info">
                                                    View Transcript
                                                </a>
                                            @endif

                                            @if (!isset($renderedFullTranscriptButtons[$fullTranscriptKey]) && in_array(Auth::user()->usertype, ['admin', 'exam_officer']))
                                                @php $renderedFullTranscriptButtons[$fullTranscriptKey] = true; @endphp
                                                <form method="POST" action="{{ route('admin.results.transcript.full', $first->user_id) }}" class="mt-2">
                                                    @csrf
                                                    <input type="hidden" name="department_id" value="{{ $first->department_id }}">
                                                    <button type="submit" class="btn btn-sm btn-secondary">
                                                        {{ $first->full_transcript_path ? 'Regenerate Full' : 'Generate Full' }}
                                                    </button>
                                                </form>

                                                @if($first->full_transcript_path)
                                                    <a href="{{ asset(ltrim($first->full_transcript_path, '/')) }}"
                                                        target="_blank"
                                                        class="btn btn-sm btn-dark mt-1">
                                                        View Full Transcript
                                                    </a>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.results.editGroup', [$first->user_id, $first->session, $first->semester, 'department_id' => $first->department_id]) }}" class="btn btn-sm btn-warning">Edit All</a>

                                            @if($first->department_id !== $first->user->department_id && in_array(Auth::user()->usertype, ['admin', 'exam_officer']))
                                                <form method="POST" action="{{ route('admin.results.migrateDepartmentResults', $first->user_id) }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="source_department_id" value="{{ $first->department_id }}">
                                                    <input type="hidden" name="target_department_id" value="{{ $first->user->department_id }}">
                                                    <button type="submit" class="btn btn-sm btn-info">
                                                        Copy To {{ $first->user->department->name ?? 'Current Dept' }}
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- End responsive table -->

                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
