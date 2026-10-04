@extends('layouts.dash')
@section('content')
<div class="main-panel"><div class="content-wrapper">
<h1 class="h3">Review activation: {{ $target->name }}</h1>
<p>Current session: {{ $source?->name ?? 'None' }}. Only students you select below will move up one level. All others keep their current level. Activating or renaming a session no longer promotes students automatically.</p>
<p>Promotion checks require complete enrollment details, published results in both semesters and no missing or unresolved results. Students may have at most {{ $maxCarryovers }} outstanding carryover course(s). Graduation cases need separate review. These checks do not replace your institution's promotion approval. <a href="{{ route('admin.promotion-policy.edit') }}">Edit promotion policy</a></p>
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form method="POST" action="{{ route('admin.academic-sessions.activate', $target) }}">@csrf
<input type="hidden" name="preview_token" value="{{ $token }}">
<div class="card mb-3"><div class="card-body table-responsive"><table class="table"><thead><tr><th>Promote</th><th>Student</th><th>Department</th><th>Current level</th><th>Proposed level</th><th>Standing / checks</th></tr></thead><tbody>
@forelse($rows as $row)<tr>
<td>@if($row['eligible'])<input type="checkbox" name="student_ids[]" value="{{ $row['student']->id }}" aria-label="Promote {{ $row['student']->name }}">@else<span>Review needed</span>@endif</td>
<td>{{ $row['student']->name }}</td><td>{{ $row['student']->department?->name ?? 'Missing' }}</td><td>{{ $row['student']->level }}</td><td>{{ $row['eligible'] ? $row['next'] : 'Unchanged' }}</td>
<td>
    {{ $row['standing'] }}
    @if($row['carryovers'] !== null)
        <br>Carryovers: {{ $row['carryovers'] }} / {{ $maxCarryovers }} allowed
    @endif
    <br>{{ $row['eligible'] ? 'Available for your approval' : implode(' ', $row['issues']) }}
</td>
</tr>@empty<tr><td colspan="6">No students.</td></tr>@endforelse
</tbody></table></div></div>
<label class="d-block mb-3"><input type="checkbox" name="confirmed" value="1" required> I have reviewed the session and selected promotions. Published tuition schedules will generate missing invoices for the resulting student levels.</label>
<button class="btn btn-primary">Activate session and apply selected promotions</button>
<a class="btn btn-light" href="{{ route('dashboard') }}#academic-sessions-panel">Cancel</a>
</form>
</div></div>
@endsection
