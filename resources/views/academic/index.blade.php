@extends('academic.layout')
@section('heading', 'Results and approvals')
@section('academic-content')
@if(session('import_errors'))<div class="alert alert-warning"><ul>@foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@include('academic.filters')
@if(auth()->user()->dashboardRole() !== 'student')
<p>Only published results are visible to students. {{ \App\Services\Academic\AcademicStanding::REPEAT_FAILURE_THRESHOLD }} or more outstanding failed courses in a session means repeat.</p>
<p>{{ auth()->user()->dashboardRole() === 'admin' ? 'As admin, you may submit, review and approve your own results. All workflow stages and completeness checks still apply.' : 'After submission, another authorized staff member must review and approve your results.' }}</p>
<div class="mb-3"><a class="btn btn-primary" href="{{ route(auth()->user()->dashboardRole() === 'lecturer' ? 'lecturer.results.create' : 'academic.entry') }}">Enter result</a> <a class="btn btn-secondary" href="{{ route(auth()->user()->dashboardRole() === 'lecturer' ? 'lecturer.results.upload' : 'academic.upload') }}">Import workbook</a></div>
@endif
<form method="POST" action="{{ route('academic.batch') }}">@csrf
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th><label class="d-inline-flex align-items-center gap-2 mb-0"><input type="checkbox" id="select-all-results" aria-label="Select all results"><span>Select</span></label></th><th>Student</th><th>Course</th><th>Session</th><th>Semester</th><th>Outcome</th><th>Score / Grade</th><th>Workflow</th><th>Attempt</th><th>Details</th></tr></thead><tbody>
@forelse($results as $result)
<tr><td>@if(auth()->user()->dashboardRole() !== 'student')<input type="checkbox" class="result-row-checkbox" name="result_ids[]" value="{{ $result->id }}" aria-label="Select result {{ $result->id }}">@endif</td><td>{{ $result->user?->name }}<br>{{ $result->matric_number }}</td><td>{{ $result->course_code }}</td><td>{{ $result->session }}</td><td>{{ $result->semester }}</td><td>{{ str_replace('_',' ', $result->outcome_status) }}</td><td>{{ $result->score ?? '—' }} / {{ $result->grade ?? '—' }}</td><td>{{ ucfirst($result->workflow_status) }}</td><td>{{ $result->attempt_number }} ({{ $result->attempt_type }})</td><td><a href="{{ route('academic.show',$result) }}">Open v{{ $result->version }}</a></td></tr>
@empty<tr><td colspan="10">No results match these filters.</td></tr>@endforelse
</tbody></table></div>
@if(auth()->user()->dashboardRole() !== 'student')
<div class="row g-2"><div class="col-md-3"><label for="batch_action">Action for selected results</label><select id="batch_action" name="action" class="form-control"><option value="submit">Submit</option>@if($manager)<option value="review">Review</option><option value="approve">Approve</option><option value="publish">Publish</option><option value="return">Return to draft</option>@endif</select></div><div class="col-md-6"><label for="batch_reason">Reason / review notes</label><input id="batch_reason" name="reason" class="form-control" required minlength="5" maxlength="2000"></div><div class="col-md-3 align-self-end"><button class="btn btn-primary">Apply to selected</button></div></div>
@endif
</form>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const masterCheckbox = document.getElementById('select-all-results');
        const rowCheckboxes = Array.from(document.querySelectorAll('.result-row-checkbox'));

        if (masterCheckbox) {
            masterCheckbox.addEventListener('change', function () {
                rowCheckboxes.forEach(function (checkbox) {
                    checkbox.checked = masterCheckbox.checked;
                });
            });

            rowCheckboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    if (!checkbox.checked && masterCheckbox.checked) {
                        masterCheckbox.checked = false;
                        return;
                    }

                    const allChecked = rowCheckboxes.length > 0 && rowCheckboxes.every(function (item) {
                        return item.checked;
                    });

                    masterCheckbox.checked = allChecked;
                });
            });
        }
    });
</script>
<div class="mt-3">{{ $results->links() }}</div></div>
@endsection
