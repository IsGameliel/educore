@extends('academic.layout')
@section('heading','Result appeals')
@section('academic-content')
@if(!$manager)<form method="POST" enctype="multipart/form-data" action="{{ route('academic.appeals.store') }}" class="card card-body mb-4">@csrf
<h3>Report a missing or incorrect result</h3><div class="row g-3"><div class="col-md-4"><label>Session<select name="session" class="form-control" required>@foreach($sessions as $session)<option>{{ $session }}</option>@endforeach</select></label></div><div class="col-md-4"><label>Semester<select name="semester" class="form-control"><option>First</option><option>Second</option></select></label></div><div class="col-md-4"><label>Course code<input name="course_code" class="form-control" required maxlength="100" value="{{ old('course_code') }}"></label></div></div>
<label class="mt-3">Explain the issue<textarea name="message" class="form-control" required minlength="10" maxlength="5000">{{ old('message') }}</textarea></label><label class="mt-3">Evidence (PDF, JPG or PNG; up to 5 MB)<input type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png" class="form-control"></label><button class="btn btn-primary mt-3">Submit appeal</button></form>@endif
@forelse($appeals as $appeal)<article class="card mb-3"><div class="card-body"><h3>Appeal #{{ $appeal->id }} — {{ $appeal->course_code }}</h3><p>{{ $appeal->session }} · {{ $appeal->semester }} · Student #{{ $appeal->user_id }} · {{ $appeal->status }}</p><p>{{ $appeal->message }}</p>@if($appeal->evidence_path)<a href="{{ route('academic.appeals.evidence',$appeal) }}">Download evidence</a>@endif
@if($appeal->response)<p class="mt-2"><strong>Staff response:</strong> {{ $appeal->response }}</p>@endif
@if($manager && !in_array($appeal->status,['resolved','rejected']))<form method="POST" action="{{ route('academic.appeals.resolve',$appeal) }}" class="mt-3">@csrf<label>Status<select name="status" class="form-control"><option value="in_review">In review</option><option value="resolved">Resolved</option><option value="rejected">Rejected</option></select></label><label class="d-block">Response<textarea name="response" class="form-control" required minlength="5" maxlength="5000"></textarea></label><button class="btn btn-primary mt-2">Save response</button></form>@endif</div></article>@empty<p>No appeals yet.</p>@endforelse
{{ $appeals->links() }}
</div>
@endsection
