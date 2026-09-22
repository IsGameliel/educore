@extends('academic.layout')
@section('heading', $result->course_code.' — '.$result->matric_number)
@section('academic-content')
<p>{{ $result->session }} · {{ $result->semester }} semester · <strong>{{ ucfirst($result->workflow_status) }}</strong> · Version {{ $result->version }}</p>
<p>Outcome: {{ str_replace('_',' ',$result->outcome_status) }} · Score: {{ $result->score ?? '—' }} · Grade: {{ $result->grade ?? '—' }} · Credits: {{ $result->credit_unit }}</p>
@if($result->registration?->previousResult?->workflow_status === 'published')
<p>Carryover attempt: <a href="{{ route('academic.show', $result->registration->previousResult) }}">View previous failed result ({{ $result->registration->previousResult->session }})</a>. The original marks are preserved.</p>
@endif
@if($attempts->count() > 1)
<h3>Exam attempts</h3><p>The original exam result is retained for reference. Each resit has its own score and approval status.</p>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Exam</th><th>Score</th><th>Grade</th><th>Status</th><th>Record</th></tr></thead><tbody>
@foreach($attempts as $attempt)<tr><td>{{ $attempt->attempt_type === 'resit' ? 'Resit exam' : 'Original exam' }}</td><td>{{ $attempt->score ?? 'Not entered' }}</td><td>{{ $attempt->grade ?? '—' }}</td><td>{{ ucfirst($attempt->workflow_status) }}</td><td><a href="{{ route('academic.show', $attempt) }}">Open attempt {{ $attempt->attempt_number }}</a></td></tr>@endforeach
</tbody></table></div>
@endif
@if(!$student && $result->resit_authorized_at)<p>Resit authorized by staff #{{ $result->resit_authorized_by }} on {{ $result->resit_authorized_at->format('d M Y H:i') }}. The authorization reason is recorded in the change history.</p>@endif
@if(!$student)
@if($problems)<div class="alert alert-warning"><strong>Completeness checks</strong><ul>@foreach($problems as $problem)<li>{{ $problem }}</li>@endforeach</ul></div>@else<div class="alert alert-success">Course completeness checks passed.</div>@endif
@if($result->workflow_status === 'draft')
<h3>Edit draft</h3><form method="POST" action="{{ route('academic.update',$result) }}">@csrf @method('PUT') @include('academic.marks')
@if($manager)<label class="d-block mb-3"><input type="checkbox" name="adopt_policy" value="1"> Adopt the latest grading policy for this department/session</label>@endif
<button class="btn btn-primary">Save draft</button></form>
@elseif(in_array($result->workflow_status,['approved','published']))
<h3>Request a correction</h3><form method="POST" action="{{ route('academic.correction',$result) }}">@csrf @include('academic.marks')
@if($manager)<label class="d-block mb-3"><input type="checkbox" name="adopt_policy" value="1"> Propose regrading with the latest department/session policy</label>@endif
<button class="btn btn-warning">Submit correction for approval</button></form>
@endif
@if($manager && $result->workflow_status === 'published' && $result->outcome_status === 'graded' && $result->grade_point !== null && $result->grade_point == 0 && $result->attempt_type !== 'resit' && $result->resitAttempts->isEmpty())
<form method="POST" action="{{ route('academic.resit',$result) }}" class="card card-body mt-4">@csrf<h3>Authorize resit exam</h3><p>The original exam score will remain in the student’s record. Enter the new marks on the separate resit result.</p><label>Reason for authorizing this student to resit<input name="reason" class="form-control" minlength="5" maxlength="2000" required></label><button class="btn btn-secondary mt-2">Authorize resit</button></form>
@endif
@php
    $nextAction = ['draft'=>'submit','submitted'=>'review','reviewed'=>'approve','approved'=>'publish'][$result->workflow_status] ?? null;
    $needsAnotherReviewer = auth()->user()->dashboardRole() !== 'admin' && in_array(auth()->id(), [$result->uploaded_by, $result->submitted_by], true) && in_array($nextAction, ['review', 'approve'], true);
    $canAdvance = $nextAction && ($manager || $nextAction === 'submit') && !$needsAnotherReviewer;
    $canReturn = $manager && in_array($result->workflow_status,['submitted','reviewed']);
@endphp
@if($needsAnotherReviewer)<p class="alert alert-info">Another authorized staff member must review and approve your submission.</p>@endif
@if($canAdvance || $canReturn)
<h3 class="mt-4">Workflow</h3>
<form method="POST" action="{{ route('academic.transition',$result) }}" class="mb-4">@csrf
<label for="action">Action</label><select id="action" name="action" class="form-control mb-2">
@if($canAdvance)<option value="{{ $nextAction }}">{{ ucfirst($nextAction) }}</option>@endif
@if($canReturn)<option value="return">Return to draft</option>@endif</select>
<label for="workflow_reason">Review notes / reason</label><input id="workflow_reason" name="reason" class="form-control mb-2" required minlength="5" maxlength="2000"><button class="btn btn-primary">Record action</button></form>
@endif
<h3>Corrections</h3>
@forelse($corrections as $correction)<div class="card mb-3"><div class="card-body"><p>#{{ $correction->id }} · {{ $correction->status }} · Requested by staff #{{ $correction->requested_by }} · Base version {{ $correction->base_version }}</p><p>{{ $correction->reason }}</p><table class="table"><tbody>@foreach(['score'=>'Total score','ca_score'=>'CA','exam_score'=>'Exam','grade'=>'Grade','grade_point'=>'Grade point','credit_unit'=>'Credit units','outcome_status'=>'Outcome'] as $key=>$label)<tr><th>{{ $label }}</th><td>{{ $correction->proposed[$key] ?? '—' }}</td></tr>@endforeach</tbody></table>
@if(isset($correction->proposed['policy_snapshot']))<p>Proposed policy version {{ $correction->proposed['policy_snapshot']['version'] ?? 'Default' }}, pass mark {{ $correction->proposed['policy_snapshot']['pass_mark'] }}.</p>@endif<p>{{ $correction->decision_reason }}</p>
@if($manager && $correction->status === 'pending' && (int) auth()->id() === (int) $correction->requested_by)
<p class="alert alert-info">Another admin or exam officer must approve or reject your correction request.</p>
@endif
@if($manager && $correction->status === 'pending' && (int) auth()->id() !== (int) $correction->requested_by)<form method="POST" action="{{ route('academic.correction.decide',$correction) }}">@csrf<label>Decision<select name="decision" class="form-control"><option value="approved">Approve</option><option value="rejected">Reject</option></select></label><label class="d-block">Decision reason<input name="reason" class="form-control" required minlength="5" maxlength="2000"></label><button class="btn btn-primary mt-2">Save decision</button></form>@endif
</div></div>@empty<p>No corrections requested.</p>@endforelse
<h3>Change history</h3>
@foreach($revisions as $revision)<details class="border p-3 mb-2"><summary>{{ $revision->created_at }} · {{ $revision->action }} · Version {{ $revision->version }} · Actor #{{ $revision->actor_id }} @if($revision->approver_id) · Approver #{{ $revision->approver_id }} @endif</summary><p>{{ $revision->reason }}</p><table class="table"><thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead><tbody>
@foreach(['score'=>'Total score','ca_score'=>'CA','exam_score'=>'Exam','grade'=>'Grade','grade_point'=>'Grade point','credit_unit'=>'Credit units','outcome_status'=>'Outcome','workflow_status'=>'Workflow','attempt_type'=>'Attempt type','course_code'=>'Course','session'=>'Session','semester'=>'Semester','policy_snapshot.pass_mark'=>'Pass mark','policy_snapshot.repeat_rule'=>'CGPA repeat rule'] as $key=>$label)
<tr><th>{{ $label }}</th><td>{{ data_get($revision->before,$key) ?? '—' }}</td><td>{{ data_get($revision->after,$key) ?? '—' }}</td></tr>
@endforeach
</tbody></table></details>@endforeach
@else
<a class="btn btn-primary" href="{{ route('academic.appeals') }}">Report a missing or incorrect result</a>
@endif
</div>
@endsection
