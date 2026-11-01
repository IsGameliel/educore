<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Registration Form</title>
    <style>
        @page { size: A4 portrait; margin: 34pt 36pt 46pt; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #202c3e; font-size: 9pt; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; }
        .masthead { border-top: 5pt solid #173b5f; padding-top: 10pt; padding-bottom: 10pt; border-bottom: 1pt solid #dce3ea; }
        .institution { font-size: 13pt; font-weight: bold; color: #173b5f; line-height: 1.25; margin-bottom: 5pt; }
        .eyebrow { font-size: 8pt; letter-spacing: 1.5pt; color: #66768a; text-transform: uppercase; }
        .heading { margin: 13pt 0 3pt; font-size: 10pt; color: #173b5f; font-weight: bold; }
        .subtitle { margin: 0 0 10pt; color: #66768a; font-size: 9pt; }
        .session-band { background: #173b5f; color: #fff; margin-bottom: 12pt; }
        .session-band td { padding: 7pt 11pt; width: 33.33%; }
        .session-band .label { color: #c5d6e6; }
        .label { font-size: 7pt; text-transform: uppercase; letter-spacing: .7pt; margin-bottom: 3pt; color: #66768a; }
        .value { font-weight: bold; font-size: 10pt; overflow-wrap: break-word; }
        .student-info { table-layout: fixed; margin-bottom: 10pt; }
        .student-info td { width: 50%; padding: 0 14pt 8pt 0; vertical-align: top; }
        .section-title { font-size: 9pt; font-weight: bold; color: #173b5f; margin-bottom: 8pt; }
        .course-table { table-layout: fixed; }
        .course-table thead { display: table-header-group; }
        .course-table th { background: #eaf0f5; color: #173b5f; padding: 6pt 7pt; font-size: 7pt; text-align: left; text-transform: uppercase; letter-spacing: .4pt; border-bottom: 1pt solid #c5d2df; }
        .course-table td { padding: 6pt 7pt; border-bottom: .5pt solid #e1e7ed; vertical-align: top; font-size: 8.5pt; overflow-wrap: break-word; }
        .course-table tr { page-break-inside: avoid; }
        .course-table .alternate td { background: #f7f9fb; }
        .course-table .center { text-align: center; }
        .course-code { font-weight: bold; color: #173b5f; }
        .summary { margin-top: 11pt; border-bottom: 2pt solid #173b5f; page-break-inside: avoid; }
        .summary td { padding: 10pt 12pt; background: #eaf0f5; font-size: 9pt; }
        .summary .total { text-align: right; font-weight: bold; color: #173b5f; }
        .approval { margin-top: 18pt; page-break-inside: avoid; }
        .approval-note { margin: 0 0 19pt; color: #66768a; font-size: 8pt; }
        .signatures { table-layout: fixed; }
        .signatures td { width: 33.33%; padding-right: 18pt; vertical-align: top; }
        .signatures td:last-child { padding-right: 0; }
        .signature-line { border-top: .7pt solid #8797a8; margin-bottom: 5pt; }
        .signature-role { font-size: 8pt; font-weight: bold; color: #173b5f; }
        .signature-date { margin-top: 6pt; font-size: 7pt; color: #66768a; }
        .footer { position: fixed; bottom: -28pt; left: 0; right: 0; border-top: .5pt solid #dce3ea; padding-top: 7pt; color: #788697; font-size: 7pt; }
    </style>
</head>
<body>
    <div class="footer">Course registration record &nbsp; | &nbsp; {{ $session }} &nbsp; | &nbsp; {{ $semester }} semester</div>
    <!-- <div class="masthead">
        <div class="institution">{{ config('institution.branding.name') ?: 'Mudiame University Irrua' }}</div>
        <div class="eyebrow">Academic Affairs / Student Records</div>
    </div> -->
    <!-- <div class="heading">Course Registration Form</div>
    <p class="subtitle">Registered courses for the selected academic session and semester.</p> -->
    <table class="session-band">
        <tr>
            <td><div class="label">Academic session</div><div class="value">{{ $session }}</div></td>
            <td><div class="label">Semester</div><div class="value">{{ $semester }}</div></td>
            <td><div class="label">Session level</div><div class="value">{{ $level ?? 'Not set' }}</div></td>
        </tr>
    </table>
    <table class="student-info">
        <tr>
            <td><div class="label">Student name</div><div class="value">{{ $user->name }}</div></td>
            <td><div class="label">Matriculation number</div><div class="value">{{ $user->matric_number ?: 'Not assigned' }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Department</div><div class="value">{{ $department }}</div></td>
            <td><div class="label">Email address</div><div class="value">{{ $user->email ?? 'N/A' }}</div></td>
        </tr>
    </table>
    <div class="section-title">REGISTERED COURSES</div>
    <table class="course-table">
        <thead><tr>
            <th style="width: 6%;" class="center">No.</th>
            <th style="width: 19%;">Course code</th>
            <th style="width: 48%;">Course title</th>
            <th style="width: 10%;" class="center">Units</th>
            <th style="width: 17%;">Semester</th>
        </tr></thead>
        <tbody>
            @forelse($courses as $registration)
                <tr class="{{ $loop->even ? 'alternate' : '' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="course-code">{{ $registration->course?->code ?? 'N/A' }}</td>
                    <td>{{ $registration->course?->title ?? 'Course unavailable' }}</td>
                    <td class="center">{{ $registration->course?->credit_unit ?? 0 }}</td>
                    <td>{{ ucfirst($registration->course?->semester ?? $semester) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center">No registered courses found for this period.</td></tr>
            @endforelse
        </tbody>
    </table>
    <table class="summary"><tr>
        <td>{{ $courses->count() }} {{ $courses->count() === 1 ? 'course' : 'courses' }} registered</td>
        <td class="total">Total credit units &nbsp; {{ $totalCreditUnits ?? $courses->sum(fn ($registration) => $registration->course?->credit_unit ?? 0) }}</td>
    </tr></table>
    <div class="approval">
        <div class="section-title">AUTHORIZATION</div>
        <p class="approval-note">For review and endorsement by the designated academic officers.</p>
        <table class="signatures"><tr>
            <td><div class="signature-line"></div><div class="signature-role">Head of Department</div><div class="signature-date">Date: __________________</div></td>
            <td><div class="signature-line"></div><div class="signature-role">DAP</div><div class="signature-date">Date: __________________</div></td>
            <td><div class="signature-line"></div><div class="signature-role">Academic Affairs</div><div class="signature-date">Date: __________________</div></td>
        </tr></table>
    </div>
</body>
</html>
