@extends('layouts.dash')
@push('styles')
<link rel="stylesheet" href="{{ asset('dash/assets/css/students.css') }}">
<link rel="stylesheet" href="{{ asset('dash/assets/css/student-merge.css') }}">
@endpush
@section('content')
@php($retained = $preview['accounts']->firstWhere('id', $preview['retainedId']))
<div class="main-panel students-page"><div class="content-wrapper">
    <div class="page-header"><div><span class="students-eyebrow">REVIEW BEFORE MERGING</span><h1 class="students-title">Preview account merge</h1><p class="small-muted">Keep {{ $retained->name }} · {{ $retained->email }} · Account #{{ $retained->id }}</p></div><a href="{{ route('admin.students.merge.index') }}" class="btn btn-outline-primary">Change selection</a></div>
    @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div class="merge-notice">The retained email, password, and security settings stay in use. Other accounts will be archived and signed out. Payment references, amounts, result scores, document files, and historical identifiers remain intact.</div>
    <form method="POST" action="{{ route('admin.students.merge.store') }}">
        @csrf<input type="hidden" name="preview_token" value="{{ $token }}">
        <div class="card card-ghost"><div class="students-directory-heading"><h2>Choose profile information to keep</h2></div><div class="merge-profile">
            @foreach(\App\Services\StudentAccountMerge::PROFILE_FIELDS as $field)
                @php($suggestedSource = filled($retained->$field) ? $retained : ($preview['accounts']->first(fn ($account) => filled($account->$field)) ?? $retained))
                <div class="students-field"><label for="profile-{{ $field }}" class="form-label">{{ ucfirst(str_replace('_', ' ', $field)) }}</label><select id="profile-{{ $field }}" name="profile[{{ $field }}]" class="form-select" required>
                    @foreach($preview['accounts'] as $account)
                        <option value="{{ $account->id }}" @selected(old('profile.'.$field, $suggestedSource->id) == $account->id)>{{ $field === 'department_id' ? ($account->department?->name ?: 'Not set') : ($account->$field ?: 'Not set') }} — {{ $account->email }}</option>
                    @endforeach
                </select></div>
            @endforeach
        </div></div>
        <div class="card card-ghost merge-history"><div class="students-directory-heading"><h2>Linked records</h2><span>{{ $preview['accounts']->count() }} accounts</span></div>
            <div class="table-responsive"><table class="table"><thead><tr><th>Record type</th>@foreach($preview['accounts'] as $account)<th>#{{ $account->id }} {{ $account->id === $retained->id ? '(keep)' : '(archive)' }}</th>@endforeach</tr></thead><tbody>
            @foreach($preview['records'] as $table => $record)
                @if(count($record['rows']))<tr><td>{{ ucfirst(str_replace('_', ' ', $table)) }}</td>@foreach($preview['accounts'] as $account)<td>{{ collect($record['rows'])->where($record['column'], $account->id)->count() }}</td>@endforeach</tr>@endif
            @endforeach
            </tbody></table></div>
            <div class="merge-form-footer"><p>All distinct records move to account #{{ $retained->id }}. {{ count($preview['duplicates']) }} identical duplicate groups will be consolidated and saved in the audit archive.</p></div>
        </div>
        @if($preview['conflicts'])
            <div class="merge-conflicts" role="alert"><h2>Records need review before merging</h2><p>These accounts contain overlapping records. Resolve academic conflicts through the results or registration workflow, and financial conflicts through invoice review. Refresh this preview afterwards. The merge is blocked until these conflicts are resolved.</p>
                @foreach($preview['conflicts'] as $conflict)
                    <details><summary>{{ ucfirst(str_replace('_', ' ', $conflict['table'])) }} — {{ count($conflict['rows']) }} overlapping records</summary><div class="table-responsive"><table class="table"><thead><tr><th>Record</th><th>Account</th><th>Details</th></tr></thead><tbody>
                        @foreach($conflict['rows'] as $row)<tr><td>#{{ $row['id'] ?? '—' }}</td><td>#{{ $row[$preview['records'][$conflict['table']]['column']] }}</td><td class="merge-record-details">@foreach($row as $key => $value)@if(!in_array($key, ['created_at', 'updated_at'], true) && $value !== null)<span><strong>{{ str_replace('_', ' ', $key) }}:</strong> {{ is_scalar($value) ? $value : json_encode($value) }}</span>@endif @endforeach</td></tr>@endforeach
                    </tbody></table></div></details>
                @endforeach
                <a href="{{ route('admin.students.merge.preview', ['accounts' => $preview['accounts']->modelKeys(), 'retained_id' => $retained->id]) }}" class="btn btn-outline-primary">Refresh preview</a>
            </div>
        @endif
        @if($preview['duplicates'])
            <div class="card card-ghost merge-history"><div class="students-directory-heading"><h2>Duplicates ready to consolidate</h2></div>
                <div class="merge-confirm"><p class="small-muted">Matching enrollments will become one registration. Registration dates and actors may differ: the kept registration retains its details, and the other originals are saved in the audit archive. Linked results stay attached.</p>
                @foreach($preview['duplicates'] as $duplicate)
                    @php($winner = collect($duplicate['rows'])->sortBy(fn ($row) => [(int) $row[$preview['records'][$duplicate['table']]['column']] === $retained->id ? 0 : 1, $row['id']])->first())
                    <p class="small-muted">{{ ucfirst(str_replace('_', ' ', $duplicate['table'])) }}: keep record #{{ $winner['id'] }}; consolidate records {{ collect($duplicate['rows'])->where('id', '!=', $winner['id'])->pluck('id')->map(fn ($id) => '#'.$id)->implode(', ') }}.</p>
                @endforeach
                </div>
            </div>
        @endif
        <div class="card card-ghost merge-history"><div class="merge-confirm">
            <label class="form-label" for="merge-reason">Reason for merging</label><textarea id="merge-reason" name="reason" class="form-control" rows="3" minlength="10" maxlength="2000" required placeholder="Explain how you verified these accounts belong to the same student.">{{ old('reason') }}</textarea>
            <label class="merge-check"><input type="checkbox" name="confirm_identity" value="1" required @checked(old('confirm_identity'))><span>I verified that these accounts belong to the same student and reviewed the information to retain.</span></label>
            <button class="btn brand-btn" type="submit" @disabled(count($preview['conflicts']) > 0)>Confirm merge into account #{{ $retained->id }}</button>
        </div></div>
    </form>
</div></div></div>
@endsection
