@extends('layouts.dash')
@section('content')
<div class="main-panel"><div class="content-wrapper">
<h1 class="h3">Course pass marks by department</h1>
<p>Set each course's minimum pass score in its department and session. Existing results keep their saved grading policy.</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form method="GET" class="card card-body mb-3"><div class="row g-3">
<div class="col-md-5"><label for="mark-department">Department</label><select id="mark-department" name="department_id" class="form-control"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>@endforeach</select></div>
<div class="col-md-5"><label for="mark-session">Session</label><select id="mark-session" name="academic_session_id" class="form-control"><option value="">All sessions</option>@foreach($sessions as $session)<option value="{{ $session->id }}" @selected(request('academic_session_id') == $session->id)>{{ $session->name }}</option>@endforeach</select></div>
<div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">Filter courses</button></div>
</div></form>
<form method="POST" action="{{ route('admin.courses.passmarks.update') }}" class="card card-body">@csrf
<div class="table-responsive"><table class="table"><thead><tr><th>Department</th><th>Session</th><th>Course</th><th>Semester / level</th><th>Pass mark (0–100)</th></tr></thead><tbody>
@forelse($courses as $course)
@php($currentMark = $course->pass_mark ?? \App\Services\Academic\Grading::policy($course->department_id, $course->academicSession?->name ?? '')['pass_mark'])
<tr><td>{{ $course->department?->name }}</td><td>{{ $course->academicSession?->name }}</td><td>{{ $course->code }} — {{ $course->title }}</td><td>{{ $course->semester }} / {{ $course->level }}</td><td><input type="number" name="course_pass_marks[{{ $course->id }}]" min="0" max="100" required value="{{ old('course_pass_marks.'.$course->id, $currentMark) }}" aria-label="Pass mark for {{ $course->code }} in {{ $course->department?->name }}" class="form-control"></td></tr>
@empty<tr><td colspan="5">No courses match these filters.</td></tr>@endforelse
</tbody></table></div>
@if($courses->isNotEmpty())<button class="btn btn-primary">Save course pass marks on this page</button>@endif
</form><div class="mt-3">{{ $courses->links() }}</div>
</div></div>
@endsection
