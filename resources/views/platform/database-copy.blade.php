@if(auth('platform')->user()->role === 'owner')
@php($databaseCopy = \App\Models\Central\InstitutionDatabaseCopy::find($institution->id))
<section class="card"><h2>Institution database copy and primary connection</h2>
@if($databaseCopy?->direction === 'inbound')
<p><strong>Dedicated primary database:</strong> this institution manages academic records in its own database. Euvion receives verified central copies of the institution records. The last successful copy is retained if synchronization fails.</p>
@else
<p>The shared database remains authoritative. A separate database receives verified snapshots of this institution's records, including updates and deletions. This is a delayed copy, not a historical backup or a second writable school portal.</p>
@endif
@if($databaseCopy)
<p>Status: <strong>{{ $databaseCopy->status }}</strong> · Database: {{ $databaseCopy->database_name }}<br>
Last verified snapshot: {{ $databaseCopy->last_synced_at?->format('d M Y H:i:s') ?? 'Not synchronized yet' }} · Rows: {{ number_format($databaseCopy->row_count) }}
@if($databaseCopy->enabled && ($databaseCopy->last_synced_at ?? $databaseCopy->updated_at)->lt(now()->subMinutes(20)))<br><strong>Delayed: check the scheduler and central worker.</strong>@endif
@if($databaseCopy->failure_code)<br>Last failure stage: {{ $databaseCopy->failure_code }}. Check connection permissions, target ownership, schema and the configured row limit.@endif</p>
@if(!$databaseCopy->enabled && $databaseCopy->direction === 'inbound')<form method="post" action="{{ route('platform.institutions.copy.resume', $institution->id) }}">@csrf<label for="copy-resume-reason">Reason for resuming</label><input id="copy-resume-reason" name="reason" minlength="10" maxlength="2000" required><button>Resume central synchronization</button></form>@endif
@if($databaseCopy->enabled)<form method="post" action="{{ route('platform.institutions.copy.sync', $institution->id) }}">@csrf<button>Queue synchronization now</button></form>
<form method="post" action="{{ route('platform.institutions.copy.pause', $institution->id) }}">@csrf<label for="copy-pause-reason">Reason for pausing</label><input id="copy-pause-reason" name="reason" minlength="10" maxlength="2000" required><button class="secondary">Pause synchronization</button></form>@endif
@endif
@if($databaseCopy?->direction !== 'inbound')
<details><summary>{{ $databaseCopy ? 'Change connection or resume synchronization' : 'Configure a separate institution database' }}</summary>
<p>Create an empty database on the shared MySQL server (MySQL 8+ or MariaDB 10.6.1+). Give a dedicated user access only to that copy database, including SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER and INDEX. Connection credentials are encrypted and are never displayed again. Platform and other schools' databases must not be accessible to that user.</p>
<form method="post" action="{{ route('platform.institutions.copy.save', $institution->id) }}">@csrf
<label for="copy-database">Copy database name</label><input id="copy-database" name="database_name" maxlength="64" pattern="[a-zA-Z0-9_]+" value="{{ old('database_name', $databaseCopy?->database_name) }}" required>
<label for="copy-username">Dedicated database username</label><input id="copy-username" name="username" maxlength="80" pattern="[a-zA-Z0-9_]+" value="{{ old('username', $databaseCopy?->username) }}" required autocomplete="off">
<label for="copy-password">Database password {{ $databaseCopy ? '(leave blank to retain the current password)' : '' }}</label><input id="copy-password" name="password" type="password" maxlength="255" autocomplete="new-password" @required(!$databaseCopy)>
<label for="copy-reason">Reason</label><input id="copy-reason" name="reason" minlength="10" maxlength="2000" required>
<label><input type="checkbox" name="confirm_copy" value="1" required>I verified this is a dedicated institution-copy database. Initial connection requires an empty database; future snapshots replace the managed copy, including deletions.</label>
<button>Save and enable synchronization</button></form></details>
<p>After configuring the connection, an operator can activate this database as the institution's primary using the verified migration procedure.</p>
@endif
<p class="muted">Copies are queued by the scheduler about every ten minutes and retried after failures. Keep the central queue worker running. Live sessions, reset/API tokens and queued jobs are excluded. Retain independent backups.</p>
</section>
@endif
