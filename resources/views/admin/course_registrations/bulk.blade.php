@extends('layouts.dash')

@section('content')
<div class="main-panel"><div class="content-wrapper">
    <div class="page-header"><div><h3 class="page-title">Bulk course registration</h3><p class="text-muted mb-0">Register the same course selection for students in one department, session and level.</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.course-registrations.index') }}">Manage individual registrations</a></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card"><div class="card-body">
        <form method="GET" action="{{ route('admin.course-registrations.bulk') }}" class="row g-3 align-items-end mb-4">
            <div class="col-md-3"><label class="form-label">Academic session</label><select class="form-control" name="session" required>@foreach($sessions as $option)<option value="{{ $option }}" @selected($session === $option)>{{ $option }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Department</label><select class="form-control" name="department_id" required><option value="">Choose department</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string)$departmentId === (string)$department->id)>{{ $department->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Level</label><select class="form-control" name="level" required><option value="">Choose level</option>@foreach(['100','200','300','400','500','600'] as $option)<option value="{{ $option }}" @selected($level === $option)>{{ $option }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Semester</label><select class="form-control" name="semester">@foreach(['First','Second'] as $option)<option value="{{ $option }}" @selected($semester === $option)>{{ $option }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Load students</button></div>
        </form>
        @if($departmentId && $session && $level)
            @if($students->isEmpty())<div class="alert alert-info">No students have a recorded {{ $level }} level for {{ $session }} in this department.</div>
            @elseif($courses->isEmpty())<div class="alert alert-info">No courses are available for this department, level, session and semester.</div>
            @else
                @if($usingOtherSessionCatalog)
                    <div class="alert alert-warning"><strong>No exact {{ $session }} course records matched.</strong> These courses were found for the same department, level and semester under another or unassigned session. Check the source session below. You must confirm they are valid for {{ $session }} before registering.</div>
                @endif
                <form method="POST" action="{{ route('admin.course-registrations.bulk.store') }}">@csrf
                    <input type="hidden" name="department_id" value="{{ $departmentId }}"><input type="hidden" name="session" value="{{ $session }}"><input type="hidden" name="level" value="{{ $level }}"><input type="hidden" name="semester" value="{{ $semester }}">
                    <h5>Select students <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="document.querySelectorAll('[name=\'student_ids[]\']').forEach(x=>x.checked=true)">Select all</button></h5>
                    <div class="row mb-4">@foreach($students as $student)<div class="col-md-4 col-lg-3"><label class="form-check"><input class="form-check-input" type="checkbox" name="student_ids[]" value="{{ $student->id }}" @checked(in_array($student->id, old('student_ids', [])))><span class="form-check-label">{{ $student->name }} <small class="text-muted">{{ $student->matric_number }}</small></span></label></div>@endforeach</div>
                    <h5>Select courses for {{ $semester }} semester</h5><div class="table-responsive"><table class="table"><thead><tr><th></th><th>Code</th><th>Course</th><th>Units</th><th>Type</th><th>Catalog session</th></tr></thead><tbody>
                    @foreach($courses as $course)<tr><td><input type="checkbox" name="course_ids[]" value="{{ $course->id }}" @checked(in_array($course->id, old('course_ids', [])))></td><td>{{ $course->code }}</td><td>{{ $course->title }}</td><td>{{ $course->credit_unit }}</td><td>{{ $course->course_status }}</td><td>{{ $course->academicSession?->name ?? 'Not assigned' }}</td></tr>@endforeach
                    </tbody></table></div>
                    @if($usingOtherSessionCatalog)<label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="confirm_other_session_catalog" value="1" required @checked(old('confirm_other_session_catalog') === '1')><span class="form-check-label">I confirmed these catalog courses are valid for {{ $session }}.</span></label>@endif
                    <button class="btn btn-primary" type="submit">Register selected courses for students</button>
                </form>
            @endif
        @endif
    </div></div>
</div></div>
</div>
@endsection
