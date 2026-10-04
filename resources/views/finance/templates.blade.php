@extends('finance.layout')
@section('heading', 'Reusable tuition fee templates')
@section('finance-content')
<p>Save an existing fee schedule as a template, then reuse its fee breakdown for new cohorts. Templates create drafts only. Review and publish each draft before billing students.</p>
@if(in_array(auth()->user()->dashboardRole(), ['admin','bursar']))
<form method="POST" action="{{ route('finance.templates.store') }}" class="card card-body mb-4">@csrf
<h2 class="h4">Save a schedule as a template</h2>
<label>Template name<input name="name" value="{{ old('name') }}" required maxlength="120" class="form-control" placeholder="Example: First semester science fees"></label>
<label class="mt-2">Source schedule<select name="schedule_id" class="form-control" required><option value="">Select a schedule</option>@foreach($schedules as $schedule)<option value="{{ $schedule->id }}">{{ $schedule->academicSession->name }} / {{ $schedule->department->name }} / {{ $schedule->level }} / {{ $schedule->category }} / {{ $schedule->period }} — ₦{{ number_format($schedule->amount/100,2) }}</option>@endforeach</select></label>
<button class="btn btn-primary mt-3">Save template</button>
</form>
@endif
@forelse($templates as $template)
<div class="card mb-4"><div class="card-body"><h2 class="h4">{{ $template->name }}</h2><p>{{ $template->period }} billing — ₦{{ number_format($template->amount/100,2) }} @if($template->period === 'Annual') — {{ $template->first_percent }}% initially @endif</p>
<ul>@foreach($template->items as $item)<li>{{ $item['label'] }}: ₦{{ number_format($item['amount']/100,2) }}</li>@endforeach</ul>
@if(in_array(auth()->user()->dashboardRole(), ['admin','bursar']))
<form method="POST" action="{{ route('finance.templates.apply', $template) }}">@csrf
<div class="row g-3">
<div class="col-md-4"><label>Academic session<select name="academic_session_id" class="form-control" required>@foreach($sessions as $session)<option value="{{ $session->id }}">{{ $session->name }}</option>@endforeach</select></label></div>
<div class="col-md-4"><label>Category<select name="category" class="form-control"><option value="new">New students</option><option value="returning">Returning students</option></select></label></div>
<div class="col-md-4"><label>Payment deadline<input type="date" name="due_date" required class="form-control"></label></div>
@if($template->period === 'Annual' && $template->first_percent < 100)<div class="col-md-4"><label>Second-semester balance deadline<input type="date" name="second_due_date" required class="form-control"></label></div>@endif
<div class="col-md-6"><fieldset><legend class="h6">Departments</legend>@foreach($departments as $department)<label class="d-block"><input type="checkbox" name="department_ids[]" value="{{ $department->id }}"> {{ $department->name }}</label>@endforeach</fieldset></div>
<div class="col-md-6"><fieldset><legend class="h6">Levels</legend>@foreach(['100','200','300','400','500','600'] as $level)<label class="d-block"><input type="checkbox" name="levels[]" value="{{ $level }}"> {{ $level }}</label>@endforeach</fieldset></div>
</div><p class="text-muted mt-3">Creates a draft for every selected department and level. Existing schedules for the same cohort and period are skipped.</p><button class="btn btn-primary">Create draft schedules</button>
</form>
<form method="POST" action="{{ route('finance.templates.destroy', $template) }}" class="mt-3" onsubmit="return confirm('Delete this reusable template? Existing schedules and invoices will remain.');">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete template</button></form>
@endif
</div></div></div>
@empty<p>No templates yet. Create a tuition schedule first, then save it as a template here.</p>@endforelse
</div>
@endsection
