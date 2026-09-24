<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Full Transcript - {{ $student->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 30px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header img { max-width: 100px; margin-bottom: 10px; }
        .header h1 { font-size: 18pt; margin: 10px 0; }
        h1, h2, h3 { text-align: center; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        th { background-color: #f2f2f2; }
        .semester-title { background-color: #ddd; font-weight: bold; padding: 5px; text-align: left; }
        .summary { margin-top: 20px; }
        .summary p { margin: 6px 0; }
        .signatories { width: 100%; margin-top: 50px; border: none; }
        .signatories td { width: 33.33%; border: none; text-align: center; padding-top: 30px; }
        .sign-line { display: inline-block; width: 180px; border-top: 1px solid #000; padding-top: 6px; }
        .footer { margin-top: 40px; text-align: center; font-size: 10pt; color: #555; overflow-wrap: anywhere; }
    </style>
</head>
<body>
<div class="header">
    <img src="{{ public_path('asset/images/educore.png') }}" alt="University Logo">
    <h1>{{ config('app.name', 'University') }}</h1>
    <h2>{{ $document->official ? 'Official Transcript' : 'Student Copy - Unofficial' }}</h2>
</div>

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
        <th>Faculty</th>
        <td>{{ $department->faculty->name ?? 'N/A' }}</td>
    </tr>
</table>

@php $cumulativeResults = collect(); @endphp
@foreach($groups as $label=>$results)
    <div class="semester-section">
        <div class="semester-title">{{ $label }}</div>
        <table>
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Title</th>
                    <th>Unit</th>
                    <th>Score</th>
                    <th>Grade Point</th>
                    <th>Grade Letter</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>
            @foreach($results as $result)
                <tr>
                    <td>{{ $result->course_code }}</td>
                    <td>{{ $result->course_title }}</td>
                    <td>{{ $result->credit_unit }}</td>
                    <td>{{ $result->score ?? '—' }}</td>
                    <td>{{ $result->grade_point ?? '—' }}</td>
                    <td>{{ $result->grade ?? '—' }}</td>
                    <td>{{ strtoupper($result->grade ?? '') === 'F' ? 'Fail' : 'Pass' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @php
    $cumulativeResults = $cumulativeResults->concat($results);
    $cumulativeCreditRegistered = $cumulativeResults->sum('credit_unit');
    $cumulativeGradePoint = $cumulativeResults->sum(fn ($result) => $result->credit_unit * ($result->grade_point ?? 0));
    $cumulativeGpa = \App\Services\Academic\AcademicStanding::gpa($cumulativeResults);
    @endphp
    <div class="summary">
        <p>Total Credit Registered: {{ $results->sum('credit_unit') }} · Total Credit Earned: {{ $results->filter(fn ($result) => $result->outcome_status === 'graded' && $result->grade_point > 0)->sum('credit_unit') }} · Total Grade Point: {{ $results->sum(fn ($result) => $result->credit_unit * ($result->grade_point ?? 0)) }} · Semester GPA: {{ \App\Services\Academic\AcademicStanding::gpa($results) ?? 'N/A' }}</p>
        <p>Cumulative Total Credit Registered: {{ $cumulativeCreditRegistered }} · Cumulative Total Grade Point: {{ $cumulativeGradePoint }} · CGPA: {{ $cumulativeGpa ?? 'N/A' }}</p>
    </div>
@endforeach
@php
    $cumulativeResults = $groups->flatten(1);
    $cumulativeCreditRegistered = $cumulativeResults->sum('credit_unit');
    $cumulativeGradePoint = $cumulativeResults->sum(fn ($result) => $result->credit_unit * ($result->grade_point ?? 0));
@endphp
<div class="summary">
    <h3>Overall Summary</h3>
    <p>Cumulative Total Credit Registered: {{ $cumulativeCreditRegistered }} · Cumulative Total Grade Point: {{ $cumulativeGradePoint }} · CGPA: {{ $standing['cgpa'] ?? 'N/A' }}</p>
    <p>Earned credits: {{ $standing['earnedCredits'] }} · Standing: {{ $standing['standing'] }}</p>
</div>

<table class="signatories">
    <tr>
        <td><span class="sign-line">Examination Officer</span></td>
        <td><span class="sign-line">Head of Department</span></td>
        <td><span class="sign-line">Dean</span></td>
    </tr>
</table>

<div class="footer">
    <p>Document version {{ $document->version }} · Issued {{ $document->created_at }} · Issuing staff #{{ $document->issued_by }}</p>
    <p>Verification code: {{ $document->verification_code }}<br>Verify: {{ route('academic.verify', $document->verification_code) }}</p>
</div>
</body></html>
