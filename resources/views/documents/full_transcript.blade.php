<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Full Transcript - {{ $student->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 30px; }
        h1, h2, h3 { text-align: center; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        th { background-color: #f2f2f2; }
        .semester-title { background-color: #ddd; font-weight: bold; padding: 5px; text-align: left; }
        .summary { margin-top: 20px; }
    </style>
</head>
<body>

<h1>{{ config('app.name', 'University') }}</h1>
<h2>Official Transcript</h2>

<h3>Student Details</h3>
<table>
    <tr>
        <th>Name</th>
        <td>{{ $student->name }}</td>
        <th>Matric No.</th>
        <td>{{ $student->matric_number }}</td>
    </tr>
    <tr>
        <th>Department</th>
        <td>{{ $department->name ?? 'N/A' }}</td>
        <th>Email</th>
        <td>{{ $student->email }}</td>
    </tr>
</table>

@foreach($transcriptData as $key => $data)
    @php
        [$session, $semester] = explode('_', $key);
    @endphp
    <div class="semester-section">
        <div class="semester-title">Session: {{ $session }} | Semester: {{ $semester }}</div>
        <table>
            <thead>
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
                @foreach($data['results'] as $result)
                    <tr>
                        <td>{{ $result->course_code }}</td>
                        <td>{{ $result->course_title }}</td>
                        <td>{{ $result->credit_unit }}</td>
                        <td>{{ $result->score }}</td>
                        <td>{{ $result->grade }}</td>
                        <td>{{ $result->grade_point }}</td>
                    </tr>
                @endforeach
                <tr>
                    <th colspan="2">Total</th>
                    <td>{{ $data['totalCreditUnits'] }}</td>
                    <td colspan="2">GPA</td>
                    <td>{{ $data['gpa'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>
@endforeach

<div class="summary">
    <h3>Overall CGPA: {{ $cgpa ?? 'N/A' }}</h3>
</div>

</body>
</html>
