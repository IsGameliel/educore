@extends('academic.layout')
@section('heading','Student services')
@section('academic-content')
<nav class="mb-4"><a href="#onboarding">Onboarding</a> · <a href="#appeals">Appeals</a> · <a href="#transcripts">Transcripts</a> · <a href="#updates">Updates</a> · <a href="#graduation">Graduation preparation</a></nav>
<section class="card mb-4" id="onboarding"><div class="card-body"><h3 class="h5">Your onboarding checklist</h3>
<p>{{ collect($onboarding)->where('done',true)->count() }} of {{ count($onboarding) }} steps complete. This checklist updates from your records.</p>
<ul class="list-group list-group-flush">@foreach($onboarding as $step)<li class="list-group-item"><strong>{{ $step['done'] ? 'Complete' : 'Next step' }} — {{ $step['label'] }}</strong><p>{{ $step['detail'] }} <a href="{{ $step['url'] }}">View details</a></p></li>@endforeach</ul>
<p class="mt-3"><a href="{{ route('student.schedule') }}">View your timetable</a> · <a href="{{ route('academic.assistance') }}">Registration assistance and attendance</a></p>
</div></section>
<section class="card mb-4" id="appeals"><div class="card-body"><h3 class="h5">Appeal tracking</h3>
<p><a href="{{ route('academic.appeals') }}">Submit an appeal or view evidence</a>. Payment confirmation submits your request for review.</p>
@forelse($appeals as $appeal)
<article class="border-bottom mb-3"><h4 class="h6">Appeal #{{ $appeal->id }} — {{ $appeal->course_code }} / {{ $appeal->session }} / {{ $appeal->semester }}</h4>
<p><strong>{{ str_replace('_',' ',ucfirst($appeal->status)) }}</strong> · Created {{ $appeal->created_at->format('d M Y H:i') }} · Last updated {{ $appeal->updated_at->format('d M Y H:i') }}</p>
<p>{{ match($appeal->status) {'awaiting_payment'=>'Complete payment to submit your appeal.', 'open'=>'Payment confirmed. Your appeal is waiting for academic review.', 'in_review'=>'The academic office is reviewing your appeal.', 'resolved'=>'Review completed. Any grade change follows the result-correction approval process.', 'rejected'=>'Review completed. Read the staff response below.', default=>'Contact the academic office for clarification.'} }}</p>
@if($appeal->response)<p>Staff response: {{ $appeal->response }}</p>@endif
@if($appeal->status === 'awaiting_payment' && $appeal->payment)<p><a href="{{ route('payments.show',$appeal->payment) }}">Continue or check payment</a></p>@endif
</article>
@empty<p>No appeals yet.</p>@endforelse
{{ $appeals->fragment('appeals')->links() }}</div></section>
<section class="card mb-4" id="transcripts"><div class="card-body"><h3 class="h5">Transcript requests and notifications</h3>
<p><a href="{{ route('academic.transcripts') }}">Request an official transcript</a>. Issued means the document is available in the portal; it does not confirm delivery to an external recipient.</p>
@forelse($transcripts as $item)
<article class="border-bottom mb-3"><h4 class="h6">Request #{{ $item->id }} — {{ str_replace('_',' ',ucfirst($item->status)) }}</h4><p>{{ $item->purpose }}</p><p>{{ $item->decision_reason }}</p>
<p>{{ match($item->status) {'awaiting_payment'=>'Payment is required before review.', 'pending'=>'Payment confirmed; awaiting an academic-office decision.', 'issued'=>'Check your issued documents below for the current download status.', 'rejected'=>'Read the decision reason. Contact the academic office before making another paid request.', default=>'Contact the academic office for an update.'} }}</p>
@if($item->status === 'awaiting_payment' && $item->payment)<p><a href="{{ route('payments.show',$item->payment) }}">Continue or check payment</a></p>@endif
</article>@empty<p>No transcript requests yet.</p>@endforelse
{{ $transcripts->fragment('transcripts')->links() }}
<h4 class="h6">Official documents</h4>
@forelse($documents as $document)<p>Request #{{ $document->transcript_request_id }} · Version {{ $document->version }} · {{ ucfirst($document->status) }}
@if($document->status === 'valid')<a href="{{ route('documents.transcripts.show',basename($document->path)) }}">Download PDF</a>@else — {{ $document->revocation_reason }}@endif
</p>@empty<p>No official documents issued.</p>@endforelse
{{ $documents->fragment('transcripts')->links() }}</div></section>
<section class="card mb-4" id="updates"><div class="card-body"><h3 class="h5">Service update history</h3><p>Status and response changes recorded since tracking was enabled. Earlier requests retain their current status above. Times use {{ config('app.timezone') }}.</p>
@forelse($events as $event)<article class="border-bottom mb-2"><strong>{{ $event->description }}</strong><p>{{ $event->created_at->format('d M Y H:i') }}</p>@if(data_get($event->properties,'response'))<p>{{ data_get($event->properties,'response') }}</p>@endif</article>@empty<p>No new service updates.</p>@endforelse
{{ $events->fragment('updates')->links() }}</div></section>
<section class="card mb-4" id="graduation"><div class="card-body"><h3 class="h5">Graduation preparation</h3>
<p>This is an academic preparation check based on published records through the active session. Final graduation approval and any non-academic clearance remain with the institution.</p>
@if(!$graduation)<p>Your department and an active academic session are required. Contact the academic office.</p>
@else
@if(!$graduation['configured'])<div class="alert alert-warning">Graduation requirements are not fully configured. The academic office must set required courses, credits and minimum CGPA before eligibility can be assessed.</div>@endif
<p><strong>{{ $graduation['eligible'] ? 'Recorded academic requirements met — awaiting institutional review.' : 'Preparation still needs attention.' }}</strong></p>
<ul><li>Earned credits: {{ $graduation['earnedCredits'] }} / {{ $graduation['policy']['graduation_credits'] ?? 'Not configured' }}</li>
<li>CGPA: {{ $graduation['cgpa'] ?? 'No published graded results' }} / required {{ $graduation['policy']['graduation_cgpa'] ?? 'Not configured' }}</li>
<li>Outstanding failed courses: {{ $graduation['outstanding']->pluck('course_code')->implode(', ') ?: 'None recorded' }}</li>
<li>Required courses not yet passed: {{ $graduation['missingRequired']->implode(', ') ?: 'None listed' }}</li>
<li>Unresolved published results: {{ $graduation['unresolved']->pluck('course_code')->implode(', ') ?: 'None recorded' }}</li>
<li>Registered courses without a published result: {{ $graduation['missingResults']->map(fn($registration)=>$registration->course->code.' ('.$registration->session.' / '.$registration->semester.')')->implode(', ') ?: 'None recorded' }}</li></ul>
<p><a href="{{ route('academic.index') }}">Review published results</a> · <a href="{{ route('academic.appeals') }}">Report a result issue</a> · <a href="{{ route('tuition.index') }}">Review tuition balances</a></p>
@endif
</div></section>
</div>
@endsection
