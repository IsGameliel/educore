@extends('layouts.dash')

@section('content')
@php
    $routePrefix = auth()->user()->usertype === 'lecturer' ? 'lecturer' : 'admin';
    $isLecturer = auth()->user()->usertype === 'lecturer';
    $canManageTranscripts = in_array(Auth::user()->usertype, ['admin', 'exam_officer']);
    $renderedFullTranscriptButtons = [];
@endphp

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
                            <form method="GET" action="{{ route($routePrefix.'.results.index') }}">
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
                                        <select name="session" id="session" class="form-select">
                                            <option value="">All Sessions</option>
                                            @foreach ($academicSessions as $academicSession)
                                                <option value="{{ $academicSession }}" {{ request('session') === $academicSession ? 'selected' : '' }}>
                                                    {{ $academicSession }}
                                                </option>
                                            @endforeach
                                        </select>
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
                            @unless ($isLecturer)
                                <form method="GET" action="{{ route($routePrefix.'.results.export') }}" class="mt-3">
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
                            @endunless
                        </div>
                    </div>

                    @if ($isLecturer)
                        <div class="alert alert-info">
                            Uploaded result groups are listed below. Lecturers can only edit or delete the uploaded results they are allowed to manage.
                        </div>
                        <div class="mb-3">
                            <a href="{{ route($routePrefix.'.results.create') }}" class="btn btn-primary">Add Result</a>
                            <a href="{{ route($routePrefix.'.results.upload') }}" class="btn btn-primary">Upload Results</a>
                        </div>
                    @else
                        <div class="mb-3">
                            <a href="{{ route($routePrefix.'.results.create') }}" class="btn btn-primary">Add Result</a>
                            <a href="{{ route($routePrefix.'.results.upload') }}" class="btn btn-primary">Upload Results</a>

                            @if(request('session') && request('semester') && $canManageTranscripts)
                                <form method="POST" action="{{ route('admin.results.transcripts.bulk.bySemester', [request('semester'), 'session' => request('session')]) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success">Generate All Transcripts for {{ request('session') }} {{ request('semester') }}</button>
                                </form>
                            @endif
                        </div>
                    @endif

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
                                    @unless ($isLecturer)
                                        <th>Transcript</th>
                                    @endunless
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($groupedResults as $group)
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
                                        @unless ($isLecturer)
                                            <td>
                                                <form method="POST" action="{{ route($routePrefix.'.results.transcript.generate.bySemester', [$first->user_id, $first->semester, 'session' => $first->session]) }}">
                                                    @csrf
                                                    <input type="hidden" name="department_id" value="{{ $first->department_id }}">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        {{ $first->transcript_path ? 'Regenerate' : 'Generate' }}
                                                    </button>
                                                </form>

                                                @if($first->transcript_path)
                                                    <a href="{{ route('documents.transcripts.show', ['filename' => basename($first->transcript_path)]) }}" 
                                                        target="_blank" 
                                                        class="btn btn-sm btn-info">
                                                        View Transcript
                                                    </a>
                                                @endif

                                                @if (!isset($renderedFullTranscriptButtons[$fullTranscriptKey]) && $canManageTranscripts)
                                                    @php $renderedFullTranscriptButtons[$fullTranscriptKey] = true; @endphp
                                                    <form method="POST" action="{{ route('admin.results.transcript.full', $first->user_id) }}" class="mt-2">
                                                        @csrf
                                                        <input type="hidden" name="department_id" value="{{ $first->department_id }}">
                                                        <button type="submit" class="btn btn-sm btn-secondary">
                                                            {{ $first->full_transcript_path ? 'Regenerate Full' : 'Generate Full' }}
                                                        </button>
                                                    </form>

                                                    @if($first->full_transcript_path)
                                                        <a href="{{ route('documents.transcripts.show', ['filename' => basename($first->full_transcript_path)]) }}"
                                                            target="_blank"
                                                            class="btn btn-sm btn-dark mt-1">
                                                            View Full Transcript
                                                        </a>
                                                    @endif
                                                @endif
                                            </td>
                                        @endunless
                                        <td>
                                            <a href="{{ route($routePrefix.'.results.editGroup.bySemester', [$first->user_id, $first->semester, 'session' => $first->session, 'department_id' => $first->department_id]) }}" class="btn btn-sm btn-warning">
                                                {{ $isLecturer ? 'Edit' : 'Edit All' }}
                                            </a>

                                            @if ($isLecturer)
                                                <form method="POST" action="{{ route($routePrefix.'.results.destroyGroup.bySemester', [$first->user_id, $first->semester, 'session' => $first->session]) }}" class="d-inline" onsubmit="return confirm('Delete all uploaded results for this student, session, and semester?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="department_id" value="{{ $first->department_id }}">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        Delete
                                                    </button>
                                                </form>
                                            @elseif($first->department_id !== $first->user->department_id && $canManageTranscripts)
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
                                @empty
                                    <tr>
                                        <td colspan="{{ $isLecturer ? 8 : 9 }}" class="text-center text-muted py-4">
                                            No result groups found for the selected filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($groupedResults->hasPages())
                        <div class="results-pagination-wrap">
                            <div class="text-muted small">
                                Showing {{ $groupedResults->firstItem() }} to {{ $groupedResults->lastItem() }} of {{ $groupedResults->total() }} result group(s)
                            </div>
                            <div class="results-pagination-links">
                                {{ $groupedResults->onEachSide(1)->links() }}
                            </div>
                        </div>
                    @endif
                    <!-- End responsive table -->

                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('styles')
    <style>
        .results-pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 1rem 0.25rem 0;
        }

        .results-pagination-links nav {
            margin: 0;
        }

        .results-pagination-links .pagination {
            margin-bottom: 0;
            gap: 0.35rem;
            align-items: center;
        }

        .results-pagination-links .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            min-width: 36px;
            padding: 0 0.75rem;
            border-radius: 8px;
            border: 1px solid #d7deea;
            color: #374151;
            margin: 0;
            text-align: center;
            font-size: 0.88rem;
            font-weight: 600;
            line-height: 1;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
            transition: all 0.18s ease;
        }

        .results-pagination-links .page-link:hover {
            color: #1f2937;
            background: #f8fafc;
            border-color: #c7d2e3;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
        }

        .results-pagination-links .page-item:first-child .page-link,
        .results-pagination-links .page-item:last-child .page-link {
            min-width: auto;
            padding: 0 0.85rem;
            font-size: 0.8rem;
            letter-spacing: 0.01em;
        }

        .results-pagination-links .page-item.active .page-link {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #fff;
            box-shadow: 0 8px 18px rgba(79, 70, 229, 0.22);
        }

        .results-pagination-links .page-item.disabled .page-link {
            background: #f8fafc;
            color: #9ca3af;
            border-color: #e5e7eb;
            box-shadow: none;
        }

        .results-pagination-links .page-item.active .page-link:hover,
        .results-pagination-links .page-item.disabled .page-link:hover {
            box-shadow: inherit;
        }

        .results-pagination-links svg {
            width: 14px;
            height: 14px;
        }

        @media (max-width: 767.98px) {
            .results-pagination-wrap {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endpush
