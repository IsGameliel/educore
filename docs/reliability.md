# Reliability operations

## Admin monitoring

Open **System & Account → System Health**, or **System health & enrollment checks** on the admin dashboard. This page is admin-only.

- Scheduler heartbeat: expected each minute; overdue after five minutes.
- Queue worker heartbeat: recorded at most once every thirty seconds for the configured default queue connection; overdue after five minutes. A heartbeat confirms the worker loop is alive, not message delivery. Restart an existing worker after deploying this code so it loads the listener.
- Scheduled backup: shows start, completion and failure; a successful run is overdue after eight days. Manual dashboard backups do not claim that the weekly task ran.
- Payment reconciliation: scheduled every ten minutes; overdue after thirty minutes. Deferred verification attempts appear as a warning.
- A command running longer than thirty minutes is marked stalled. Failed-job and pending-job counts are displayed where their configured database stores support them. Failed job payloads and credentials are not exposed.

No recorded heartbeat is shown as **Not observed**, never as healthy. The page does not launch workers, retry jobs, send notifications or perform a restore. Monitoring uses the portal database; database outages still require external server monitoring.

## Running background services

For local development, use separate terminals from the project directory:

```powershell
C:\xampp\php\php.exe artisan schedule:work
C:\xampp\php\php.exe artisan queue:work --sleep=3 --tries=3 --timeout=60
```

The queue worker processes real pending jobs, including queued email. Do not start a second scheduler if one is already managed by the server. For production, use a managed worker and run `php artisan schedule:run` once a minute through the operating system scheduler. An application code change alone does not install these services.

After starting the configured services, refresh System Health and verify fresh heartbeats. Review failed jobs using `php artisan queue:failed`; investigate before retrying specific jobs. Restart long-lived workers after deployments. The application's configured timezone governs displayed times and the weekly backup time; currently check the timezone shown on System Health.

## Scheduled backups

`php artisan backup:database` uses the same validated dump implementation, Windows environment handling, MariaDB/MySQL option detection and operation lock as dashboard backups/restores. The default directory is `storage/app/backups/database`. Existing `--connection`, `--path` and `--cleanup-only` options remain available. Cleanup-only runs do not reset backup health to successful.

Completed dumps are published only after header/completion checks. Partial files are removed on failure. Retention runs after a successful dump (or explicit cleanup-only), affects only complete matching-database SQL files directly in the chosen folder, and preserves symlinks, incomplete files and other database files. Default retention is 120 days via `DB_BACKUP_RETENTION_DAYS`. This does not back up uploaded files.

## Enrollment completeness

Student creation, editing and bulk imports require an entry year between 1900 and 2100, a valid department and a supported level. Import columns are:

```text
name,email,matric_number,level,department_id,entry_year
```

Invalid rows are reported and skipped; valid rows are imported. Existing records are not assigned guessed entry years. System Health lists incomplete records and entry years after the active session, with links to edit each student. A future entry year can indicate that the active session is wrong, not necessarily that the student is wrong. Confirm the institution's source record before editing. Issued tuition invoices retain their enrollment snapshots.

## Reviewed session activation and promotion

1. Open **Academic Sessions** on the admin dashboard and choose **Review activation**.
2. Review the source/target sessions and each student's checks.
3. Select only students whose promotion is approved. No checkbox is selected automatically.
4. Confirm the review and activate. Leaving all students unselected changes the active session without promoting anyone.

Available promotions move one level into the immediately following session. They require complete enrollment, published results in both source semesters, no unresolved/missing results, no existing target-session invoice and no recorded promotion into that target. Admins set the maximum allowed outstanding failed courses under Academic Setup → Promotion Policy; the default is zero. The limit is inclusive and counts distinct outstanding courses across current and earlier sessions. Students above the limit cannot be selected or promoted. Graduation candidates and levels 500 and 600 require separate program-duration/graduation decisions. No CGPA promotion threshold is applied. Policy changes invalidate existing activation previews.

The server rechecks eligibility and rejects stale previews and forged selections. Promotions store source/target sessions, old/new levels and approving admin. A unique student/target-session constraint prevents repeating a promotion after switching backwards and forwards. Session renaming never promotes students and is blocked once linked academic, invoice or progression records exist. Missing tuition invoices are generated after approved promotions.

This is conservative approval support, not a replacement for the institution's promotion rules. Changing to an older session does not reverse previous promotions. Correct an exceptional enrollment through authorized student management after reviewing academic and billing history.
