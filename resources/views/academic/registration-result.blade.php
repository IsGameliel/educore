@extends('layouts.dash')
@section('content')
<div class="main-panel"><div class="content-wrapper">
    <h3>Enter result for registered course</h3>
    <div class="card"><div class="card-body">
        <p><strong>{{ $registration->student->name }}</strong> ({{ $registration->student->matric_number }})</p>
        <p>{{ $registration->course->code }} — {{ $registration->course->title }}<br>
            {{ $registration->session }} · {{ $registration->semester }} semester · {{ $registration->course->credit_unit }} credit units</p>
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route(auth()->user()->dashboardRole() === 'lecturer' ? 'lecturer.results.store' : 'academic.results.store') }}">
            @csrf
            <input type="hidden" name="user_id" value="{{ $registration->user_id }}">
            <input type="hidden" name="course_id" value="{{ $registration->course_id }}">
            <input type="hidden" name="department_id" value="{{ $registration->course->department_id }}">
            <input type="hidden" name="session" value="{{ $registration->session }}">
            <input type="hidden" name="semester" value="{{ $registration->semester }}">
            <div class="form-group"><label for="outcome_status">Outcome</label>
                <select id="outcome_status" name="outcome_status" class="form-control">
                    @foreach(['graded','absent','incomplete','withheld','deferred','withdrawn','not_submitted'] as $outcome)
                        <option value="{{ $outcome }}" @selected(old('outcome_status', 'graded') === $outcome)>{{ ucfirst(str_replace('_', ' ', $outcome)) }}</option>
                    @endforeach
                </select>
            </div>
            @foreach(['ca_score' => 'CA score', 'exam_score' => 'Exam score', 'score' => 'Total score (when CA and exam are blank)'] as $field => $label)
                <div class="form-group"><label for="{{ $field }}">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" class="form-control" type="number" min="0" max="100" step="0.01" value="{{ old($field) }}">
                </div>
            @endforeach
            <p class="text-muted">CA and exam marks are added automatically when supplied. Saving creates a draft; students see the result only after publication.</p>
            <button class="btn btn-primary" type="submit">Save draft result</button>
        </form>
    </div></div>
</div></div></div>
@endsection
