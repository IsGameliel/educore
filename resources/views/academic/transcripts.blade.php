@extends('academic.layout')
@section('heading','Transcript requests and documents')
@section('academic-content')
<form method="POST" action="{{ route('academic.transcripts.request') }}" class="card card-body mb-4">@csrf
<h3>Request an official transcript</h3>@if($manager)<label>Student ID<input type="number" name="user_id" min="1" class="form-control" required></label>@endif
<label>Purpose / recipient<input name="purpose" class="form-control" required minlength="5" maxlength="1000"></label><button class="btn btn-primary mt-2">Submit request</button></form>
<h3>Requests</h3>
@forelse($requests as $item)<div class="card mb-3"><div class="card-body"><p>Request #{{ $item->id }} · Student #{{ $item->user_id }} · {{ $item->status }} · {{ $item->created_at }}</p><p>{{ $item->purpose }}</p><p>{{ $item->decision_reason }}</p>
@if($manager && $item->status === 'pending')<form method="POST" action="{{ route('academic.transcripts.decide',$item) }}">@csrf<label>Decision<select name="decision" class="form-control"><option value="issue">Issue official transcript</option><option value="reject">Reject request</option></select></label><label class="d-block">Reason<input name="reason" class="form-control" required minlength="5" maxlength="2000"></label><button class="btn btn-primary mt-2">Record decision</button></form>@endif</div></div>@empty<p>No requests yet.</p>@endforelse
{{ $requests->links() }}
<h3>Issued documents</h3><div class="table-responsive"><table class="table"><thead><tr><th>Student</th><th>Version</th><th>Type</th><th>Status</th><th>Document</th><th>Actions</th></tr></thead><tbody>
@foreach($documents as $document)<tr><td>#{{ $document->user_id }}</td><td>{{ $document->version }}</td><td>{{ $document->official ? 'Official' : 'Student copy' }}</td><td>{{ $document->status }}<br>{{ $document->revocation_reason }}</td><td>@if($document->status === 'valid')<a href="{{ route('documents.transcripts.show',basename($document->path)) }}">Download PDF</a>@endif @if($document->official)<br><a href="{{ route('academic.verify',$document->verification_code) }}">Verify document</a>@endif</td><td>@if($manager && $document->status === 'valid')<form method="POST" action="{{ route('academic.transcripts.revoke',$document) }}">@csrf<label>Revocation reason<input name="reason" class="form-control" required minlength="5" maxlength="2000"></label><button class="btn btn-danger btn-sm mt-1">Revoke</button></form>@endif</td></tr>@endforeach
</tbody></table></div></div>
@endsection
