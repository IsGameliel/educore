<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3"><label for="department">Department / programme</label><select id="department" name="department_id" class="form-control"><option value="">Select department</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label for="session">Academic session</label><select id="session" name="session" class="form-control"><option value="">Select session</option>@foreach($sessions as $session)<option @selected(request('session') === $session)>{{ $session }}</option>@endforeach</select></div>
    <div class="col-md-2"><label for="semester">Semester</label><select id="semester" name="semester" class="form-control"><option value="">Both semesters</option>@foreach(['First','Second'] as $semester)<option @selected(request('semester') === $semester)>{{ $semester }}</option>@endforeach</select></div>
    <div class="col-md-2 align-self-end"><button class="btn btn-primary">Apply filters</button></div>
</form>
