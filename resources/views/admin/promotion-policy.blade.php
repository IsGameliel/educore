@extends('layouts.dash')

@section('content')
<div class="main-panel"><div class="content-wrapper">
    <h1 class="h3 mb-3">Promotion Policy</h1>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card"><div class="card-body">
        <p>Set the maximum number of outstanding failed courses a student may carry into the next level. This limit applies to all students.</p>
        <form method="POST" action="{{ route('admin.promotion-policy.update') }}">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="max_carryovers">Maximum allowed carryovers</label>
                <input type="number" class="form-control @error('max_carryovers') is-invalid @enderror" id="max_carryovers" name="max_carryovers" min="0" max="1000" step="1" required value="{{ old('max_carryovers', $policy->max_carryovers) }}" aria-describedby="carryover-help">
                @error('max_carryovers')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small id="carryover-help" class="text-muted">For example, a limit of 2 allows students with 0, 1 or 2 carryovers. Students with 3 or more cannot be promoted. Set 0 to require no carryovers.</small>
            </div>
            <p>Carryovers include outstanding failed courses from the current and earlier sessions. A course passed later no longer counts as a carryover.</p>
            <p>Students must still meet the other promotion checks, including published results in both semesters and no missing or unresolved results. Graduation CGPA settings remain separate.</p>
            <p>Saving this policy does not change student levels. Select eligible students on the session activation review to approve their promotion.</p>
            <button class="btn btn-primary" type="submit">Save promotion policy</button>
            <a class="btn btn-light" href="{{ route('dashboard') }}#academic-sessions-panel">Academic sessions</a>
        </form>
    </div></div>
</div></div></div>
@endsection
