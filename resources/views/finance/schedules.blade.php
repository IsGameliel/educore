@extends('finance.layout')
@section('heading', 'Tuition fee schedules')
@section('finance-content')
<p>Create and approve fees before enabling registration clearance. Published invoices keep their original fee breakdown and enrollment details.</p>
@if($activeSession && $missingStudents->isNotEmpty())
<div class="card mb-4 border-warning"><div class="card-body">
    <h2 class="h4">Billing setup needs attention — {{ $activeSession->name }}</h2>
    <p>These students are missing invoices for one or both semesters (up to 50 shown). Check the active session, enrollment details and published schedules, then generate missing invoices.</p>
    <div class="table-responsive"><table class="table"><thead><tr><th>Student</th><th>Department</th><th>Level</th><th>Entry year</th><th>Enrollment check</th></tr></thead><tbody>
    @foreach($missingStudents as $student)<tr><td>{{ $student->name }}</td><td>{{ $student->department?->name ?? 'Missing department' }}</td><td>{{ $student->level ?? 'Missing level' }}</td><td>{{ $student->entry_year ?? 'Missing entry year' }}</td><td>{{ !$student->entry_year ? 'Add entry year before billing' : ((int) $student->entry_year > $activeSession->start_year ? 'Entry year is after the active session' : 'Check published fee coverage') }}</td></tr>@endforeach
    </tbody></table></div>
    <p class="text-muted mb-0">Session activation now requires a review. Only explicitly selected eligible students are promoted; other levels remain unchanged.</p>
</div></div>
@endif
@if(in_array(auth()->user()->dashboardRole(), ['admin','bursar']))
<form method="POST" action="{{ $editing ? route('finance.schedules.update', $editing) : route('finance.schedules.store') }}" class="card card-body mb-4">@csrf @if($editing) @method('PUT') @endif
    <h2 class="h4">{{ $editing ? 'Edit '.$editing->status.' schedule' : 'Create draft schedule' }}</h2>
    @if($editing?->status === 'published')<p class="alert alert-info">Changes apply to future invoices. Existing invoices and payments keep their original amounts, deadlines and payment policy.</p>@endif
    @if($editing && $editing->invoices_count > 0)<p class="text-muted">This schedule has issued invoices. Keep its session, department, level, category and billing period unchanged.</p>@endif
    <div class="row g-3">
        <div class="col-md-4"><label for="session">Academic session</label><select id="session" name="academic_session_id" class="form-control" required>@foreach($sessions as $session)<option value="{{ $session->id }}" @selected(old('academic_session_id', $editing?->academic_session_id) == $session->id)>{{ $session->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label for="department">Department</label><select id="department" name="department_id" class="form-control" required>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id', $editing?->department_id) == $department->id)>{{ $department->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label for="level">Level</label><select id="level" name="level" class="form-control">@foreach(['100','200','300','400','500','600'] as $level)<option @selected(old('level', $editing?->level) == $level)>{{ $level }}</option>@endforeach</select></div>
        <div class="col-md-4"><label for="category">Student category</label><select id="category" name="category" class="form-control">@foreach(['new'=>'New students','returning'=>'Returning students'] as $value=>$label)<option value="{{ $value }}" @selected(old('category', $editing?->category) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-4"><label for="period">Billing period</label><select id="period" name="period" class="form-control">@foreach(['Annual','First','Second'] as $period)<option @selected(old('period', $editing?->period) === $period)>{{ $period }}</option>@endforeach</select></div>
        <div class="col-md-4"><label for="first-percent">First-semester percentage (annual only)</label><input id="first-percent" type="number" name="first_percent" min="1" max="100" value="{{ old('first_percent', $editing?->first_percent ?? 100) }}" class="form-control" required><small>100 requires full payment. A lower percentage enables two installments.</small></div>
        <div class="col-md-6"><label for="due-date">Payment deadline</label><input id="due-date" type="date" name="due_date" value="{{ old('due_date', $editing?->due_date?->format('Y-m-d')) }}" class="form-control" required></div>
        <div class="col-md-6"><label for="second-date">Second-semester balance deadline (installments)</label><input id="second-date" type="date" name="second_due_date" value="{{ old('second_due_date', $editing?->second_due_date?->format('Y-m-d')) }}" class="form-control"></div>
    </div>
    <h3 class="h5 mt-4">Fee breakdown (NGN)</h3>
    @php($feeRows = old('labels', $editing ? array_column($editing->items, 'label') : ['Tuition', '', '']))
    <div id="fee-items">@foreach($feeRows as $index => $label)
        <div class="row g-2 mb-2"><div class="col-md-7"><input name="labels[]" aria-label="Fee item description" placeholder="Fee description" maxlength="120" value="{{ $label }}" class="form-control"></div><div class="col-md-5"><input name="amounts[]" aria-label="Fee item amount in naira" type="number" step="0.01" min="0.01" max="99999999.99" placeholder="Amount in naira" value="{{ old('amounts.'.$index, isset($editing?->items[$index]) ? number_format($editing->items[$index]['amount'] / 100, 2, '.', '') : '') }}" class="form-control"></div></div>
    @endforeach</div>
    <div class="d-flex gap-2 mt-3"><button type="button" class="btn btn-outline-secondary" id="add-fee-item">Add fee item</button><button class="btn btn-primary">{{ $editing?->status === 'published' ? 'Save changes' : 'Save draft' }}</button>@if($editing)<a href="{{ route('finance.schedules') }}" class="btn btn-light">Cancel edit</a>@endif</div>
</form>
@endif
<div class="card mb-4"><div class="card-body"><h2 class="h4">Schedules</h2><div class="table-responsive"><table class="table"><thead><tr><th>Session / cohort</th><th>Period</th><th>Total</th><th>Policy</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($schedules as $schedule)<tr><td>{{ $schedule->academicSession->name }}<br>{{ $schedule->department->name }} · {{ $schedule->level }} · {{ $schedule->category }}</td><td>{{ $schedule->period }}</td><td>₦{{ number_format($schedule->amount / 100, 2) }}</td><td>{{ $schedule->first_percent }}% initially<br>Due {{ $schedule->due_date->format('d M Y') }}</td><td>{{ ucfirst($schedule->status) }}</td><td>
    @if(auth()->user()->dashboardRole() === 'admin' || ($schedule->status === 'draft' && auth()->user()->dashboardRole() === 'bursar'))<a class="btn btn-outline-primary btn-sm" href="{{ route('finance.schedules', ['edit' => $schedule->id]) }}">Edit</a>@endif
    @if($schedule->status === 'draft' && auth()->user()->dashboardRole() === 'admin')<form method="POST" action="{{ route('finance.schedules.publish', $schedule) }}" class="mt-2" onsubmit="return confirm('Publish these fees and generate invoices for matching students in the active session?');">@csrf<button class="btn btn-primary btn-sm">Publish</button></form>@endif
    @if(auth()->user()->dashboardRole() === 'admin')<form method="POST" action="{{ route('finance.schedules.destroy', $schedule) }}" class="mt-2" onsubmit="return confirm('Delete this schedule and stop issuing new invoices from it? Existing invoices and payments will be preserved.');">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete</button></form>@endif
</td></tr>@empty<tr><td colspan="6">No fee schedules yet.</td></tr>@endforelse
</tbody></table></div>{{ $schedules->links() }}</div></div>
<div class="card"><div class="card-body"><h2 class="h4">Session billing controls</h2><p>New students are identified by their entry year matching the session start year. Verify student department, level and entry year before billing. Future-session invoices are generated after that session is activated and levels are updated.</p>
@foreach($sessions as $session)<div class="border-top py-3"><strong>{{ $session->name }}</strong> {{ $session->is_active ? '(active)' : '' }} — Clearance {{ $session->tuition_enabled ? 'enabled' : 'not enabled' }}
    <div class="d-flex flex-wrap gap-2 mt-2">
        @if($session->is_active && in_array(auth()->user()->dashboardRole(), ['admin','bursar']))<form method="POST" action="{{ route('finance.sessions.generate', $session) }}">@csrf<button class="btn btn-outline-primary btn-sm">Generate missing invoices</button></form>@endif
        @if(!$session->tuition_enabled && auth()->user()->dashboardRole() === 'admin')<form method="POST" action="{{ route('finance.sessions.enable', $session) }}" onsubmit="return confirm('Require tuition clearance for course registration in this session? Unpaid students will need to pay or receive an approved exemption.');">@csrf<button class="btn btn-primary btn-sm">Enable tuition clearance</button></form>@endif
    </div>
</div>@endforeach
</div></div></div>
@endsection
@section('scripts')
<script>
(() => {
    const period = document.getElementById('period');
    const percentage = document.getElementById('first-percent');
    const secondDate = document.getElementById('second-date');
    if (period && percentage && secondDate) {
        const syncPolicy = () => {
            const annual = period.value === 'Annual';
            const installments = annual && Number(percentage.value) > 0 && Number(percentage.value) < 100;
            percentage.disabled = !annual;
            percentage.required = annual;
            percentage.parentElement.hidden = !annual;
            secondDate.disabled = !installments;
            secondDate.required = installments;
            secondDate.parentElement.hidden = !installments;
        };
        period.addEventListener('change', syncPolicy);
        percentage.addEventListener('input', syncPolicy);
        syncPolicy();
    }
    document.getElementById('add-fee-item')?.addEventListener('click', function () {
        const container = document.getElementById('fee-items');
        if (container.children.length >= 20) return;
        const row = container.firstElementChild.cloneNode(true);
        row.querySelectorAll('input').forEach(input => input.value = '');
        container.appendChild(row);
    });
})();
</script>
@endsection
