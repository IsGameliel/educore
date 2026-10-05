@extends('layouts.dash')
@push('styles')
<link rel="stylesheet" href="{{ asset('dash/assets/css/system-health.css') }}">
@endpush
@section('content')
@php
    $healthy = collect($rows)->where('status', 'success')->count();
    $alerts = collect($rows)->whereIn('status', ['failed', 'overdue', 'stalled', 'warning'])->count();
    $unobserved = collect($rows)->where('status', 'not observed')->count();
    $tones = ['success' => 'good', 'failed' => 'danger', 'stalled' => 'danger', 'overdue' => 'warning', 'warning' => 'warning', 'running' => 'info', 'synchronous' => 'info'];
    $timestamp = fn ($value) => $value ? \Carbon\Carbon::parse($value, config('app.timezone'))->format('d M Y, H:i') : 'Not recorded';
@endphp
<div class="main-panel"><div class="content-wrapper health-page">
    <header class="health-hero">
        <div>
            <div class="health-eyebrow"><i class="mdi mdi-pulse" aria-hidden="true"></i> EUVION / OPERATIONS</div>
            <h1>System health</h1>
            <p>A clear view of background services, queue activity, and enrollment data that needs attention.</p>
            <span class="health-hero-status"><span aria-hidden="true"></span>{{ $alerts ? $alerts.' service alert(s) need attention' : ($unobserved ? $unobserved.' service(s) awaiting an observation' : 'No service alerts flagged') }}</span>
        </div>
        <div class="health-hero-actions">
            <a href="{{ route('admin.system-health') }}" class="health-refresh"><i class="mdi mdi-refresh" aria-hidden="true"></i> Refresh overview</a>
            <small>Snapshot: {{ now()->format('d M Y, H:i') }}<br>{{ config('app.timezone') }}</small>
        </div>
    </header>

    <div class="health-metrics">
        <article class="health-metric"><div class="health-metric-top"><span>Reporting success</span><i class="mdi mdi-check-circle-outline health-icon-good" aria-hidden="true"></i></div><strong>{{ $healthy }}<small> / {{ count($rows) }}</small></strong><p>Services with a recent successful run</p></article>
        <article class="health-metric"><div class="health-metric-top"><span>Service alerts</span><i class="mdi mdi-alert-circle-outline health-icon-warning" aria-hidden="true"></i></div><strong>{{ $alerts }}</strong><p>Failed, overdue, stalled, or warning</p></article>
        <article class="health-metric"><div class="health-metric-top"><span>Pending jobs</span><i class="mdi mdi-layers-outline health-icon-info" aria-hidden="true"></i></div><strong>{{ $queue['pending'] ?? '—' }}</strong><p>{{ $queue['pending'] === null ? 'Unavailable for this queue connection' : 'Jobs waiting in the queue' }}</p></article>
        <article class="health-metric"><div class="health-metric-top"><span>Enrollment reviews</span><i class="mdi mdi-account-outline health-icon-purple" aria-hidden="true"></i></div><strong>{{ number_format($students->total()) }}</strong><p>Student records needing verified details</p></article>
    </div>

    <div class="health-operations">
        <section class="health-panel">
            <div class="health-panel-heading"><div><span class="health-kicker">SERVICE MONITORING</span><h2>Scheduled tasks &amp; background work</h2><p>Latest recorded activity across your platform.</p></div><span class="health-count">{{ count($rows) }} services</span></div>
            <div class="health-services">
                @foreach($rows as $row)
                <article class="health-service">
                    <div class="health-service-top"><div class="health-service-title"><span class="health-service-icon"><i class="mdi mdi-server" aria-hidden="true"></i></span><div><h3>{{ $row['label'] }}</h3><p>{{ $row['record']?->message ?? 'No details recorded' }}</p></div></div><span class="health-badge health-badge-{{ $tones[$row['status']] ?? 'neutral' }}">{{ ucfirst($row['status']) }}</span></div>
                    <dl class="health-times"><div><dt>Last started</dt><dd>{{ $timestamp($row['record']?->started_at) }}</dd></div><div><dt>Last success</dt><dd>{{ $timestamp($row['record']?->succeeded_at) }}</dd></div><div><dt>Last failure</dt><dd>{{ $timestamp($row['record']?->failed_at) }}</dd></div></dl>
                </article>
                @endforeach
            </div>
            <p class="health-footnote"><i class="mdi mdi-information-outline" aria-hidden="true"></i> Not observed means no run has been recorded; it does not mean the service is healthy. Times use {{ config('app.timezone') }}.</p>
        </section>
        <aside class="health-sidebar">
            <section class="health-panel health-queue">
                <span class="health-kicker">BACKGROUND PROCESSING</span><h2>Queue overview</h2><p class="health-muted">Current job processing activity.</p>
                <div class="health-connection"><span>Connection</span><strong>{{ $queue['connection'] }}</strong></div>
                <div class="health-queue-stats"><div><strong>{{ $queue['pending'] ?? '—' }}</strong><span>Pending jobs</span></div><div><strong class="{{ $queue['failed'] ? 'health-icon-danger' : '' }}">{{ $queue['failed'] ?? '—' }}</strong><span>Failed jobs</span></div></div>
                @if($queue['pending'] === null || $queue['failed'] === null)<p class="health-muted">A dash indicates that the count is unavailable.</p>@endif
                @if($queue['oldest'])<div class="health-queue-oldest"><i class="mdi mdi-clock-outline" aria-hidden="true"></i> Oldest queued job: {{ \Carbon\Carbon::createFromTimestamp($queue['oldest'])->diffForHumans() }}</div>@endif
                @if(isset($queue['error']))<p class="health-error" role="alert">{{ $queue['error'] }}</p>@endif
                <p class="health-queue-note">An idle worker heartbeat confirms the worker is running, not that email was delivered. Synchronous queues run during requests and have no background worker.</p>
            </section>
            <section class="health-guide"><i class="mdi mdi-shield-check" aria-hidden="true"></i><h2>Keep operations on track</h2><p>Contact the server administrator for overdue services or failed jobs. Review enrollment issues using verified student information.</p><a href="#enrollment-review">Review enrollment data <i class="mdi mdi-arrow-right" aria-hidden="true"></i></a></section>
        </aside>
    </div>

    <section class="health-panel health-enrollment" id="enrollment-review">
        <div class="health-panel-heading"><div><span class="health-kicker">DATA QUALITY</span><h2>Enrollment needs review</h2><p>{{ number_format($students->total()) }} student record(s) need attention. Correct verified details through student management; entry years are never guessed.</p></div><span class="health-session"><i class="mdi mdi-calendar" aria-hidden="true"></i> {{ $session?->name ?? 'No active session' }}</span></div>
        <div class="table-responsive"><table class="table health-table"><thead><tr><th>Student</th><th>Department</th><th>Level</th><th>Entry year</th><th>Issues</th><th>Action</th></tr></thead><tbody>
            @forelse($students as $student)
            <tr><td><div class="health-student"><span class="health-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}</span><div><strong>{{ $student->name }}</strong><small>{{ $student->matric_number ?: 'Account #'.$student->id }}</small></div></div></td><td>{{ $student->department?->name ?? 'Missing' }}</td><td>{{ $student->level ?? 'Missing' }}</td><td>{{ $student->entry_year ?? 'Missing' }}</td><td><div class="health-issues">@foreach(\App\Services\EnrollmentData::issues($student, $session) as $issue)<span>{{ $issue }}</span>@endforeach</div></td><td><a class="health-review" href="{{ route('admin.students.edit', $student) }}">Review student <i class="mdi mdi-arrow-top-right" aria-hidden="true"></i></a></td></tr>
            @empty
            <tr><td colspan="6"><div class="health-empty"><i class="mdi mdi-check-circle-outline" aria-hidden="true"></i><strong>No enrollment issues found.</strong><p>Student enrollment details have no issues flagged for this check.</p></div></td></tr>
            @endforelse
        </tbody></table></div>
        <div class="health-pagination"><span>Showing {{ $students->firstItem() ?? 0 }}–{{ $students->lastItem() ?? 0 }} of {{ number_format($students->total()) }} records</span>{{ $students->links() }}</div>
    </section>
</div></div>
@endsection
