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
                    <th>Course Title</th>
                    <th>Credit Unit</th>
                    <th>Score</th>
                    <th>Grade</th>
                    <th>Grade Point</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($results as $result)
                    <tr>
                        <td>{{ $result->course_code }}</td>
                        <td>{{ $result->course_title }}</td>
                        <td>{{ $result->credit_unit }}</td>
                        <td>{{ $result->score }}</td>
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
                    <td>{{ number_format($gpa, 2) }}</td>
                </tr>
                @if (isset($cgpa))
                    <tr>
                        <td><strong>CGPA</strong></td>
                        <td>{{ number_format($cgpa, 2) }}</td>
                    </tr>
                    @php
                        if ($cgpa >= 4.5) {
                            $class = 'First Class';
                            $rowClass = 'table-success';
                        } elseif ($cgpa >= 3.5) {
                            $class = 'Second Class Upper (2:1)';
                            $rowClass = 'table-primary';
                        } elseif ($cgpa >= 2.4) {
                            $class = 'Second Class Lower (2:2)';
                            $rowClass = 'table-warning';
                        } elseif ($cgpa >= 1.5) {
                            $class = 'Third Class';
                            $rowClass = 'table-warning';
                        } elseif ($cgpa >= 1.0) {
                            $class = 'Pass';
                            $rowClass = 'table-secondary';
                        } else {
                            $class = 'Fail';
                            $rowClass = 'table-danger';
                        }
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td><strong>Classification</strong></td>
                        <td>{{ $class }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- Actions --}}
        @if ($results->first()->transcript_path)
            <a href="{{ asset('storage/' . $results->first()->transcript_path) }}" 
                class="btn btn-primary mt-2" download>
                Download Transcript
            </a>
        @endif

        <a href="{{ route('student.results.index') }}" class="btn btn-secondary mt-2">Back to Results</a>
    </div>
                    </div>
@endsection
