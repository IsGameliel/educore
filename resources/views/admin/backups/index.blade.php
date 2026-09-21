@extends('layouts.dash')
@section('content')
<div class="main-panel"><div class="content-wrapper">
    <div class="page-header"><h3 class="page-title">Database backups</h3><a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Dashboard</a></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="card mb-4"><div class="card-body">
        <h4>Backup &amp; Restore</h4>
        <p>Create a database copy before major changes. Download a copy and keep it somewhere outside this server.</p>
        <p class="text-muted">These SQL backups contain database records, including account details. They do not include uploaded documents, course materials, photos or transcript PDF files. Store downloads securely.</p>
        <form method="POST" action="{{ route('admin.backups.store') }}">@csrf<button class="btn btn-primary" type="submit">Create backup now</button></form>
        <p class="text-muted mt-3 mb-0">Existing scheduled backups and earlier dated backups are listed below. The displayed date is the file's last-modified time. Creating a backup here does not delete older backups.</p>
    </div></div>
    <div class="card"><div class="card-body"><h4>Available backups ({{ count($backups) }})</h4>
        <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Backup file</th><th>Folder</th><th>Last modified</th><th>Size</th><th>Actions</th></tr></thead><tbody>
            @forelse($backups as $backup)
            <tr><td>{{ $backup['name'] }}</td><td>{{ $backup['folder'] }}</td><td>{{ \Carbon\Carbon::createFromTimestamp($backup['modified'])->format('d M Y H:i') }}</td><td>{{ number_format($backup['size'] / 1024, 1) }} KB</td><td>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.backups.download', $backup['id']) }}">Download</a>
                <a class="btn btn-sm btn-outline-danger" href="{{ route('admin.backups.confirm', $backup['id']) }}">Review restore</a>
            </td></tr>
            @empty<tr><td colspan="5" class="text-center">No SQL backups found. Use Create backup now to make the first one.</td></tr>@endforelse
        </tbody></table></div>
    </div></div>
</div></div>
</div>
@endsection
