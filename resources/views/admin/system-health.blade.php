@extends('layouts.dash')
@section('content')
<div class="main-panel"><div class="content-wrapper">
    <h1 class="h3">System health &amp; enrollment checks</h1>
    <p>Refresh this page to see the latest observations. “Not observed” means no run has been recorded; it does not mean the service is healthy. Times use {{ config('app.timezone') }}.</p>
    <div class="card mb-4"><div class="card-body"><h2 class="h4">Scheduled tasks and background work</h2>
        <div class="table-responsive"><table class="table"><thead><tr><th>Service</th><th>Status</th><th>Last started</th><th>Last success</th><th>Last failure</th><th>Details</th></tr></thead><tbody>
        @foreach($rows as $row)<tr><td>{{ $row['label'] }}</td><td><span class="badge {{ $row['status'] === 'success' ? 'bg-success' : 'bg-warning text-dark' }}">{{ ucfirst($row['status']) }}</span></td><td>{{ $row['record']?->started_at ?? '—' }}</td><td>{{ $row['record']?->succeeded_at ?? '—' }}</td><td>{{ $row['record']?->failed_at ?? '—' }}</td><td>{{ $row['record']?->message ?? 'No details recorded' }}</td></tr>@endforeach
        </tbody></table></div>
        <p>Queue connection: <strong>{{ $queue['connection'] }}</strong>. Pending jobs: {{ $queue['pending'] ?? 'Not available for this driver' }}. Failed jobs: {{ $queue['failed'] ?? 'Not available' }}.</p>
        @if($queue['oldest'])<p>Oldest queued job: {{ \Carbon\Carbon::createFromTimestamp($queue['oldest'])->diffForHumans() }}.</p>@endif
        @if(isset($queue['error']))<p class="text-danger">{{ $queue['error'] }}</p>@endif
        <p class="mb-0">An idle worker heartbeat confirms the worker is running, not that email was delivered. Synchronous queues run during requests and have no background worker. Contact the server administrator for overdue services or failed jobs.</p>
    </div></div>
    <div class="card"><div class="card-body"><h2 class="h4">Enrollment needs review — {{ $session?->name ?? 'No active session' }}</h2>
        <p>{{ $students->total() }} student record(s) need attention. Correct verified details through student management; entry years are never guessed.</p>
        <div class="table-responsive"><table class="table"><thead><tr><th>Student</th><th>Department</th><th>Level</th><th>Entry year</th><th>Issues</th><th>Action</th></tr></thead><tbody>
        @forelse($students as $student)<tr><td>{{ $student->name }}</td><td>{{ $student->department?->name ?? 'Missing' }}</td><td>{{ $student->level }}</td><td>{{ $student->entry_year }}</td><td>{{ implode('; ', \App\Services\EnrollmentData::issues($student, $session)) }}</td><td><a href="{{ route('admin.students.edit', $student) }}">Review student</a></td></tr>@empty<tr><td colspan="6">No enrollment issues found.</td></tr>@endforelse
        </tbody></table></div>{{ $students->links() }}
    </div></div>
</div></div>
@endsection
