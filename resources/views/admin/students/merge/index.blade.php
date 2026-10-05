@extends('layouts.dash')
@push('styles')
<link rel="stylesheet" href="{{ asset('dash/assets/css/students.css') }}">
<link rel="stylesheet" href="{{ asset('dash/assets/css/student-merge.css') }}">
@endpush
@section('content')
<div class="main-panel students-page"><div class="content-wrapper">
    <div class="page-header">
        <div><span class="students-eyebrow">STUDENT MANAGEMENT</span><h1 class="students-title">Merge student accounts</h1><p class="small-muted">Select duplicate accounts and choose the account the student will use.</p></div>
        <a href="{{ route('admin.students.index') }}" class="btn btn-outline-primary">Back to students</a>
    </div>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div class="merge-notice">Matching names suggest possible duplicates. Verify the student's identity and matric number before merging. Different students may share a name.</div>
    <div class="card card-ghost">
        <form method="GET" class="merge-search">
            <div class="students-field"><label for="merge-search" class="form-label">Find accounts by name, email, or matric number</label><input id="merge-search" class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="e.g. ABHULIMEN TESTIMONY EJEHI"></div>
            <button class="btn brand-btn" type="submit">Search accounts</button><a class="btn btn-outline-secondary" href="{{ route('admin.students.merge.index') }}">Suggested duplicates</a>
        </form>
        <form method="GET" action="{{ route('admin.students.merge.preview') }}">
            <div class="students-directory-heading"><h2>{{ request()->filled('search') ? 'Search results' : 'Possible duplicate accounts' }}</h2><span>{{ $students->total() }} accounts</span></div>
            <div class="table-responsive"><table class="table"><thead><tr><th>Select</th><th>Keep</th><th>Student</th><th>Email</th><th>Matric number</th><th>Department / level</th></tr></thead><tbody>
            @forelse($students as $student)
                <tr>
                    <td><input type="checkbox" name="accounts[]" value="{{ $student->id }}" aria-label="Select account {{ $student->email }}" @checked(in_array($student->id, old('accounts', [])))></td>
                    <td><input type="radio" name="retained_id" value="{{ $student->id }}" aria-label="Keep account {{ $student->email }}" required @checked(old('retained_id') == $student->id)></td>
                    <td class="student-name">{{ $student->name }}<small class="merge-account-id">Account #{{ $student->id }}</small></td>
                    <td>{{ $student->email }}</td><td>{{ $student->matric_number ?: 'Not set' }}</td>
                    <td>{{ $student->department?->name ?: 'Not assigned' }}<small class="merge-account-id">{{ $student->level ?: 'No' }} level</small></td>
                </tr>
            @empty
                <tr><td colspan="6" class="students-empty"><strong>No accounts found</strong><p>Search for a student to select accounts manually.</p></td></tr>
            @endforelse
            </tbody></table></div>
            @if($students->count())<div class="merge-form-footer"><p>Select 2–10 accounts on this page. Select “Keep” for one of those accounts.</p><button class="btn brand-btn" type="submit">Preview merge <i class="mdi mdi-arrow-right"></i></button></div>@endif
        </form>
        <div class="students-pagination">{{ $students->links() }}</div>
    </div>
    <div class="card card-ghost merge-history"><div class="students-directory-heading"><h2>Recent merges</h2><span>Audit history</span></div>
        <div class="table-responsive"><table class="table"><thead><tr><th>Retained account</th><th>Archived accounts</th><th>Administrator</th><th>Reason</th><th>Date</th></tr></thead><tbody>
        @forelse($history as $merge)
            <tr><td>{{ $merge->retained_name }} (#{{ $merge->retained_user_id }})</td><td>@foreach(json_decode($merge->account_snapshots, true) as $account)@if($account['id'] != $merge->retained_user_id)<div>#{{ $account['id'] }} · {{ $account['email'] }}</div>@endif @endforeach</td><td>{{ $merge->actor_name }}</td><td class="merge-reason">{{ $merge->reason }}</td><td>{{ $merge->created_at }}</td></tr>
        @empty<tr><td colspan="5" class="students-empty">No accounts have been merged yet.</td></tr>@endforelse
        </tbody></table></div>
    </div>
</div></div>
@endsection
