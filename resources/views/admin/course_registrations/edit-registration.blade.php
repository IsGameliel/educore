@extends('layouts.dash')
@push('styles')
<link rel="stylesheet" href="{{ asset('dash/assets/css/students.css') }}">
<link rel="stylesheet" href="{{ asset('dash/assets/css/student-merge.css') }}">
@endpush
@section('content')
@php($locked = $hasResults || $registration->previous_result_id)
<div class="main-panel students-page"><div class="content-wrapper">
    <div class="page-header"><div><span class="students-eyebrow">COURSE REGISTRATIONS</span><h1 class="students-title">Edit registration #{{ $registration->id }}</h1><p class="small-muted">{{ $student->name }} · {{ $student->matric_number ?: $student->email }}</p></div>
        <a class="btn btn-outline-primary" href="{{ route('admin.course-registrations.show', ['student' => $student->id, 'semester' => $registration->semester, 'session' => $registration->session ?: 'unassigned']) }}">Back to registrations</a></div>
    @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    @if($locked)<div class="merge-notice">This registration has result history or a carryover link. Its course, session, and semester are protected. Eligible status changes remain available; academic corrections must use the result or carryover workflow.</div>@endif
    <form method="POST" action="{{ route('admin.course-registrations.record.update', ['student' => $student->id, 'registration' => $registration->id]) }}" class="card card-ghost">
        <input type="hidden" name="group_fingerprint" value="{{ $groupFingerprint }}">
        @csrf @method('PUT')<input type="hidden" name="fingerprint" value="{{ $fingerprint }}">
        @if($locked)
            <input type="hidden" name="course_id" value="{{ $registration->course_id }}"><input type="hidden" name="session" value="{{ $registration->session }}"><input type="hidden" name="semester" value="{{ $registration->semester }}">
        @endif
        <div class="students-directory-heading"><h2>Registration details</h2></div>
        <div class="merge-profile"><div class="students-field"><label class="form-label" for="update-scope">Apply session / semester changes to</label><select id="update-scope" name="update_scope" class="form-select" required>
            <option value="single" @selected(old('update_scope', $scope) === 'single')>Only this registration</option>
            <option value="bulk" @selected(old('update_scope', $scope) === 'bulk')>All {{ $groupRecords->count() }} courses in this group</option>
        </select></div></div>
        <div class="merge-notice">Single update changes only registration #{{ $registration->id }}. Bulk update applies session and semester changes to all {{ $groupRecords->count() }} courses in the original {{ $registration->session ?: 'unassigned session' }} / {{ $registration->semester }} Semester group. Course and status edits apply to the selected registration.</div>
        <div class="merge-profile"><div class="students-field"><label class="form-label">Courses affected by session / semester changes</label>@foreach($groupRecords as $row)<div>#{{ $row->id }} — {{ $row->course?->code }} — {{ $row->course?->title }} ({{ $row->status }})</div>@endforeach</div></div>
        <div class="merge-profile">
            <div class="students-field"><label class="form-label" for="registration-session">Academic session</label><select id="registration-session" name="session" class="form-select" @disabled($locked)>
                <option value="" @selected(!old('session', $registration->session))>Session not assigned</option>
                @foreach($sessions as $session)<option value="{{ $session }}" @selected(old('session', $registration->session) === $session)>{{ $session }}</option>@endforeach
            </select></div>
            <div class="students-field"><label class="form-label" for="registration-semester">Semester</label><select id="registration-semester" name="semester" class="form-select" required @disabled($locked)>
                @foreach(['First', 'Second'] as $semester)<option value="{{ $semester }}" @selected(old('semester', $registration->semester) === $semester)>{{ $semester }} Semester</option>@endforeach
            </select></div>
            <div class="students-field"><label class="form-label" for="registration-course">Course</label><select id="registration-course" name="course_id" class="form-select" required @disabled($locked)>
                @foreach($courses as $course)<option value="{{ $course->id }}" @selected(old('course_id', $registration->course_id) == $course->id)>{{ $course->code }} — {{ $course->title }} ({{ $course->academicSession?->name ?: 'Legacy offering' }}, {{ $course->semester }}, {{ $course->level }} level)</option>@endforeach
            </select><p class="small-muted">Choose the course offering for the selected session and semester.</p></div>
            <div class="students-field"><label class="form-label" for="registration-status">Status</label><select id="registration-status" name="status" class="form-select" required>
                @foreach(\App\Http\Controllers\Admin\RegistrationEditController::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status', $registration->status) === $status) @disabled($hasResults && !in_array($status, \App\Services\Academic\ResultRegistration::ELIGIBLE_STATUSES, true) && $status !== $registration->status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select></div>
        </div>
        <div class="merge-confirm"><label class="form-label" for="registration-reason">Reason for the update</label><textarea id="registration-reason" class="form-control" name="reason" rows="3" minlength="10" maxlength="2000" required placeholder="Explain the course correction or session assignment.">{{ old('reason') }}</textarea>
            <div class="merge-form-footer"><p>The selected update scope determines which registrations change. All changes and your reason are recorded in the activity history.</p><button class="btn brand-btn" type="submit">Save changes</button></div>
        </div>
    </form>
</div></div></div>
@endsection
