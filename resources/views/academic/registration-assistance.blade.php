<div class="card mb-4"><div class="card-body">
<h3 class="h5">Registration checklist</h3>
@foreach(\App\Services\EnrollmentData::issues(auth()->user(), \App\Models\AcademicSession::current()) as $issue)<p class="text-danger">{{ $issue }}. Contact the academic office to correct your enrollment.</p>@endforeach
<p>Review both semesters before choosing courses. Suggestions do not submit registration or override tuition, prerequisites or credit limits.</p>
@forelse($assistance as $semester => $entry)
<details class="mb-3"><summary>{{ $entry['session'] }} / {{ $semester }} — {{ $entry['registered'] }} registrations, {{ $entry['units'] }} / {{ $entry['limit'] }} units</summary>
<p class="mt-2">{{ $entry['clearance']['message'] }} <a href="{{ route('tuition.index') }}">Tuition details</a></p>
@foreach($entry['conflicts'] as $conflict)<p class="text-danger">{{ $conflict }} Contact the timetable administrator.</p>@endforeach
<p>Available courses not yet registered:</p><ul>
@forelse($entry['courses'] as $course)
<li>{{ $course['code'] }} ({{ $course['units'] }} units)
    @if($course['carryover'])
        — Carryover
    @endif
    @if($course['missing'])
        — Missing prerequisite registration: {{ implode(', ', $course['missing']) }}
    @endif
</li>
@empty
<li>No remaining courses found for your enrollment.</li>
@endforelse
</ul></details>
@empty<p>No active academic session. Contact the academic office.</p>@endforelse
<a href="{{ route('student.courses.registration') }}">Open course registration</a>
</div></div>
