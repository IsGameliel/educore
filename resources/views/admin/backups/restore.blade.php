@extends('layouts.dash')
@section('content')
<div class="main-panel"><div class="content-wrapper">
    <h3>Review database restore</h3>
    <a href="{{ route('admin.backups.index') }}" class="btn btn-outline-secondary mb-3">Back to backups</a>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="card"><div class="card-body">
        <h4>{{ $backup['name'] }}</h4>
        <p>Backup database: <strong>{{ $inspection['database'] ?? 'Unknown' }}</strong></p>
        <p>Tables containing saved rows: {{ implode(', ', $inspection['tables_with_data']) ?: 'None detected — this backup may contain only empty tables.' }}</p>
        <p class="text-muted">This lists tables with INSERT statements, not row counts. A backup cannot recover records that were already missing when it was created.</p>
        @if(!$inspection['complete'] || !$inspection['compatible'])
            <div class="alert alert-danger">Restore is unavailable. The backup must be a completed MySQL dump for this portal's configured database, without database-switching commands.</div>
        @else
            <div class="alert alert-warning">
                <strong>This replaces the current database tables with this backup.</strong>
                <p class="mb-0">Changes made after the backup will be lost from the restored database. Accounts and passwords revert to the backup. You will be signed out and must use an administrator account contained in it.</p>
            </div>
            <p>The backup is first imported and checked in a temporary database. The portal then enters maintenance mode and saves a new safety backup before replacing any tables. If that backup fails, restoration stops. After import, the portal applies pending schema migrations for the installed version.</p>
            <p>Choose a quiet period and ask your server administrator to pause scheduled jobs and queue workers that write to this database. If import fails, maintenance mode stays enabled and a server administrator must recover the database before reopening the portal.</p>
            <form method="POST" action="{{ route('admin.backups.restore', $backup['id']) }}">@csrf
                <input type="hidden" name="checksum" value="{{ $inspection['checksum'] }}">
                <div class="form-group"><label for="password">Your current admin password</label><input type="password" name="password" id="password" class="form-control" required autocomplete="current-password"></div>
                <div class="form-group"><label for="confirmation">Type exactly: <strong>RESTORE {{ $backup['name'] }}</strong></label><input type="text" name="confirmation" id="confirmation" class="form-control" required autocomplete="off"></div>
                <label class="d-block mb-3"><input type="checkbox" name="understand" value="1" required> I understand that this replaces the current database and I have an administrator login for this backup.</label>
                <button class="btn btn-danger" type="submit">Restore this backup</button>
            </form>
        @endif
    </div></div>
</div></div>
</div>
@endsection
