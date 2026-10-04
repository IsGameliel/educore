@extends('academic.layout')
@section('heading','Academic assistance')
@section('academic-content')
@if($role === 'student') @include('academic.registration-assistance') @endif
@if($role !== 'student')
<div class="card mb-4"><div class="card-body"><h3 class="h5">Result-review reminders</h3>
<p>Results unchanged for at least three days. Updated automatically when you open this page; completed stages disappear. Showing up to 100 course/stage groups. These reminders do not publish results.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>Course</th><th>Session / semester</th><th>Next action</th><th>Results</th><th>Waiting since</th><th></th></tr></thead><tbody>
@forelse($reviews as $review)<tr><td>{{ $review->course_code }}</td><td>{{ $review->session }} / {{ $review->semester }}</td><td>{{ ['draft'=>'Complete and submit','submitted'=>'Review','reviewed'=>'Approve','approved'=>'Publish'][$review->workflow_status] }}</td><td>{{ $review->total }}</td><td>{{ $review->waiting_since }}</td><td><a href="{{ route('academic.show',$review->example_id) }}">Open result</a></td></tr>@empty<tr><td colspan="6">No results currently need a reminder.</td></tr>@endforelse
</tbody></table></div></div></div>
@endif
<div class="card"><div class="card-body"><h3 class="h5">Attendance summaries</h3>
<form method="GET" class="d-flex flex-wrap align-items-end mb-3"><label class="mr-3">From<input type="date" name="from" value="{{ $from }}" class="form-control" required></label><label class="mr-3">To<input type="date" name="to" value="{{ $to }}" class="form-control" required></label><button class="btn btn-primary mb-2">Apply dates</button></form>
<p>Attendance rate = (present + late) / (present + late + absent). Excused records are excluded. Only recorded meetings through today are counted; missing records are not assumed absent.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>Student</th><th>Course</th><th>Present</th><th>Late</th><th>Absent</th><th>Excused</th><th>Rate</th></tr></thead><tbody>
@forelse($attendance as $row) @php($denominator = $row->total - $row->excused)
<tr><td>{{ $row->student_name }}</td><td>{{ $row->code }}</td><td>{{ $row->present }}</td><td>{{ $row->late }}</td><td>{{ $row->absent }}</td><td>{{ $row->excused }}</td><td>{{ $denominator ? number_format(100 * ($row->present + $row->late) / $denominator,1).'%' : 'Not applicable' }}</td></tr>
@empty<tr><td colspan="7">No attendance records in this date range.</td></tr>@endforelse
</tbody></table></div>{{ $attendance->links() }}</div></div></div>
@endsection
