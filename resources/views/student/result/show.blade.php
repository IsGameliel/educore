@extends('layouts.dash')

@section('content')
    <div class="container">
        <h2>Results for {{ $user->name }} ({{ $user->matric_number }})</h2>
        <p><strong>Session:</strong> {{ $session }}</p>
        <p><strong>Semester:</strong> {{ $semester }}</p>
        <p><strong>Level:</strong> {{ $results->first()->level }}</p>
        <p><strong>Program:</strong> {{ $user->department->name ?? 'N/A' }}</p>

        {{-- Results Table --}}
        <table class="table table-bordered table-striped mt-3">
            <thead class="table-dark">
                <tr>
                    <th>Course Code</th>
                    <th>Course Title</th><th>Exam attempt</th>
                    <th>Credit Unit</th>
                    <th>Outcome</th><th>Score</th>
                    <th>Grade</th>
                    <th>Grade Point</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($results as $result)
                    <tr>
                        <td>{{ $result->course_code }}</td>
                        <td>{{ $result->course_title }}</td><td>{{ $result->attempt_type === 'resit' ? 'Resit exam' : ucfirst($result->attempt_type).' exam' }} ({{ $result->attempt_number }})</td>
                        <td>{{ $result->credit_unit }}</td>
                        <td>{{ str_replace('_',' ',$result->outcome_status) }}</td><td>{{ $result->score ?? '—' }}</td>
                        <td>{{ $result->grade }}</td>
                        <td>{{ $result->grade_point }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- GPA & CGPA Summary --}}
        <table class="table table-bordered w-50 mt-4">
            <thead class="table-light">
                <tr>
                    <th colspan="2" class="text-center">Summary</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Total Credit Units</strong></td>
                    <td>{{ $totalCreditUnits }}</td>
                </tr>
                <tr>
                    <td><strong>GPA</strong></td>
                    <td>{{ $gpa === null ? 'N/A' : number_format($gpa, 2) }}</td>
                </tr>
                @if (isset($cgpa))
                    <tr>
                        <td><strong>CGPA as of this semester</strong></td>
                        <td>{{ number_format($cgpa, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <p><strong>Standing:</strong> {{ $standing['standing'] }} · {{ $standing['failedThisSession'] }} outstanding failed course(s) in this session.</p>
        <p><strong>Carryovers:</strong> {{ $standing['outstanding']->pluck('course_code')->join(', ') ?: 'None' }}</p>
        @if(auth()->user()->dashboardRole() === 'student')
        <form method="POST" action="{{ route('student.results.transcript.bySemester', [$user->id, $semester, 'session'=>$session]) }}">@csrf<button class="btn btn-primary">Download student copy</button></form>
        <a href="{{ route('academic.transcripts') }}" class="btn btn-outline-primary mt-2">Request official transcript</a>
        <a href="{{ route('academic.appeals') }}" class="btn btn-outline-primary mt-2">Report a result issue</a>
        @endif
        {{-- Actions --}}
        @if ($results->first()->transcript_path)
            <a href="{{ route('documents.transcripts.show', ['filename' => basename($results->first()->transcript_path)]) }}" 
                class="btn btn-primary mt-2" download>
                Download Transcript
            </a>
        @endif

        <a href="{{ route('student.results.index') }}" class="btn btn-secondary mt-2">Back to Results</a>
    </div>
                    </div>
@endsection
