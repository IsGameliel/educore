<!DOCTYPE html>
<html lang="en">
<head>
    <title>Academic Transcript</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; font-size: 12pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .header img { max-width: 100px; margin-bottom: 10px; }
        .header h1 { font-size: 18pt; margin: 10px 0; }
        .student-info { margin-bottom: 20px; }
        .student-info p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2f2;; }
        .summary { margin-top: 20px; }
        .footer { margin-top: 40px; text-align: center; font-size: 10pt; color: #555; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/university_logo.png') }}" alt="University Logo">
        <h1>University of Example</h1>
        <h2>Official Academic Transcript</h2>
    </div>

    <div class="student-info">
        <p><strong>Name:</strong> {{ $student->name }}</p>
        <p><strong>Matric Number:</strong> {{ $student->matric_number }}</p>
        <p><strong>Department:</strong> {{ $department->name }}</p>
        <p><strong>Program:</strong> {{ $student->program ?? 'N/A' }}</p>
        <p><strong>Level:</strong> {{ $results->first()->level }}</p>
        <p><strong>Session:</strong> {{ $results->first()->session }}</p>
        <p><strong>Semester:</strong> {{ $results->first()->semester }}</p>
    </div>

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

    <div class="summary">
        <p><strong>Total Credit Units:</strong> {{ $totalCreditUnits }}</p>
        <p><strong>GPA:</strong> {{ number_format($gpa, 2) }}</p>
        @if ($cgpa)
            <p><strong>CGPA:</strong> {{ number_format($cgpa, 2) }}</p>
        @endif
    </div>

    <div class="footer">
        <p>Issued by the Office of the Registrar, University of Example</p>
        <p>Date Issued: {{ \Carbon\Carbon::now()->format('F d, Y') }}</p>
    </div>
</body>
</html>
