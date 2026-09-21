@extends('academic.layout')
@section('heading','Academic reports')
@section('academic-content')
@include('academic.filters')
<p>Select a department and academic session. Only published grades contribute to performance and standing.</p>
@if($rows->isNotEmpty())
<a class="btn btn-outline-primary mb-3" href="{{ route('academic.reports',array_merge(request()->only(['department_id','session','semester']),['export'=>1])) }}">Export academic standing (CSV)</a>
<h3>Submission progress</h3><p>@foreach($counts as $status=>$count)<span class="badge bg-secondary me-2">{{ $status }}: {{ $count }}</span>@endforeach</p>
@if($performance)<p>Graded attempts: {{ $performance['graded'] }} · Passed: {{ $performance['passed'] }} · Failed: {{ $performance['failed'] }} · Pass rate: {{ $performance['pass_rate'] === null ? 'N/A' : $performance['pass_rate'].'%' }}</p>@endif
<h3>Grade distribution</h3><p>@foreach($distribution as $grade=>$count)<span class="badge bg-info me-2">{{ $grade }}: {{ $count }}</span>@endforeach</p>
<h3>Academic standing and graduation eligibility</h3><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Student</th><th>CGPA</th><th>Earned credits</th><th>Failed this session</th><th>Standing</th><th>Outstanding courses</th><th>Graduation</th></tr></thead><tbody>
@foreach($rows as $row) @php($r=$row['report'])<tr><td>{{ $row['student']->name }}<br>{{ $row['student']->matric_number }}</td><td>{{ $r['cgpa'] ?? '—' }}</td><td>{{ $r['earnedCredits'] }}</td><td>{{ $r['failedThisSession'] }}</td><td>{{ $r['standing'] }}</td><td>{{ $r['outstanding']->pluck('course_code')->join(', ') ?: 'None' }}</td><td>{{ !$r['configured'] ? 'Requirements not configured' : ($r['eligible'] ? 'Eligible academically' : 'Not yet eligible') }}@if($r['missingRequired']->isNotEmpty())<br>Required: {{ $r['missingRequired']->join(', ') }}@endif @if($r['missingResults']->isNotEmpty())<br>Missing/unpublished results: {{ $r['missingResults']->count() }}@endif @if($r['unresolved']->isNotEmpty())<br>Unresolved: {{ $r['unresolved']->pluck('course_code')->join(', ') }}@endif</td></tr>@endforeach
</tbody></table></div>
<h3>Departmental broadsheet</h3><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Student</th><th>Published course results</th></tr></thead><tbody>@foreach($broadsheet as $group)<tr><td>{{ $group->first()->user?->name }}<br>{{ $group->first()->matric_number }}</td><td>@foreach($group as $result)<span class="d-inline-block me-3">{{ $result->course_code }} ({{ $result->semester }}, {{ $result->attempt_type }}): {{ $result->score ?? $result->outcome_status }} / {{ $result->grade ?? '—' }}</span>@endforeach</td></tr>@endforeach</tbody></table></div>
<h3>Missing marks and completeness issues</h3><ul>@forelse(array_unique($problems) as $problem)<li>{{ $problem }}</li>@empty<li>No completeness issues found.</li>@endforelse</ul>
@endif
</div>
@endsection
