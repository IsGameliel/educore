@extends('layouts.dash')
@section('content')
<div class="main-panel"><div class="content-wrapper">
    <h1 class="h3">{{ $admin ? 'Student name-change requests' : 'Request a change of name' }}</h1>
    <p><a href="{{ $admin ? route('admin.students.index') : route('profile.show') }}">Back to {{ $admin ? 'students' : 'profile' }}</a></p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @unless($admin)
    <form method="POST" action="{{ route('name-changes.store') }}" enctype="multipart/form-data" class="card card-body mb-4">
        @csrf
        <p>Current name: <strong>{{ auth()->user()->name }}</strong>. Your name changes only after admin approval.</p>
        <label for="correct-name">Correct full name</label><input id="correct-name" name="requested_name" value="{{ old('requested_name') }}" required maxlength="255" class="form-control mb-3">
        <label for="change-reason">Reason for change</label><textarea id="change-reason" name="reason" required minlength="10" maxlength="2000" class="form-control mb-3">{{ old('reason') }}</textarea>
        <label for="name-document">Document showing the correct name</label><input id="name-document" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required class="form-control mb-2">
        <p class="text-muted">Upload a PDF, JPG or PNG up to 5 MB. Documents are accessible only to you and administrators.</p>
        <button class="btn btn-primary">Submit name-change request</button>
    </form>
    @endunless
    @forelse($requests as $change)
    <div class="card card-body mb-3">
        <h2 class="h5">{{ $change->original_name }} &rarr; {{ $change->requested_name }}</h2>
        <p>{{ $change->reason }}</p><p>Status: <strong>{{ ucfirst($change->status) }}</strong> · Submitted {{ $change->created_at->format('d M Y H:i') }}</p>
        <p><a href="{{ route('name-changes.document', $change) }}">Download supporting document</a></p>
        @if($change->review_note)<p>Admin response: {{ $change->review_note }}</p>@endif
        @if($admin && $change->status === 'pending')
        <form method="POST" action="{{ route('name-changes.review', $change) }}">@csrf
            <label for="review-{{ $change->id }}">Review note</label><textarea id="review-{{ $change->id }}" name="review_note" minlength="5" maxlength="2000" required class="form-control mb-2"></textarea>
            <button name="decision" value="approved" class="btn btn-success">Approve name change</button>
            <button name="decision" value="rejected" class="btn btn-outline-danger">Reject request</button>
        </form>
        @endif
    </div>
    @empty<p>No name-change requests yet.</p>@endforelse
    {{ $requests->links() }}
</div></div></div>
@endsection
