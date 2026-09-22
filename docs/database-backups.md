# Admin database backups and restores

## Opening the page

Sign in as **admin** and select **Backup & Restore** on the dashboard or sidebar. Exam officers, lecturers and students cannot access these actions.

The page lists SQL backups from `storage/app/backups`, including subfolders such as `database`, `before-academic-workflow` and `before-resit-update`. Each entry shows its filename, folder, last-modified time, size, **Download** and **Review restore** actions. A file's modification time may differ from its original creation time after copying.

## Creating and downloading a backup

1. Click **Create backup now**.
2. Wait for the success message. The new file appears under the `manual` folder.
3. Download it and retain a secure copy outside this server.

New files have unique names and are only listed after the dump completes. Failed/partial dashboard backups are removed. Creating a dashboard backup does not prune previous files. The existing `backup:database` command and its scheduled retention policy remain available; its default retention is 120 days in its configured command directory.

SQL backups include database account records and password hashes. They do **not** back up uploaded documents, photos, course materials, transcript PDFs, application files or `.env`. Back up private storage separately and keep downloads private.

## Restoring a listed backup

1. Select **Review restore** for the intended file.
2. Check the backup database name and the list of tables containing saved rows. A backup of an empty table cannot restore earlier missing records.
3. Confirm you know an administrator login that existed in that backup. Restoring also restores old account credentials.
4. Choose a quiet period. Ask the server administrator to pause queue workers and scheduled jobs that write to the database; website maintenance mode alone does not stop these processes.
5. Enter your **current** admin password.
6. Type the exact `RESTORE filename.sql` phrase shown and tick the acknowledgement.
7. Click **Restore this backup** once and wait.

The system checks the completed MySQL-dump marker, matching database header and file checksum. It rejects ordinary database-switching statements. Only server-held backup files are offered; this page does not accept arbitrary SQL uploads. Server administrators must keep the backup directory restricted to trusted processes.

Before touching live tables, the portal imports a private copy into a temporary MySQL database, runs pending migrations there, and checks required tables and an administrator account. This requires permission to create and remove temporary databases. Backup-only GTID and binary-log statements are stripped from the working copy; the saved backup is unchanged. New backups omit GTID state. A failed preflight leaves live tables intact.

The portal then enters maintenance mode and creates a current safety backup under `before-dashboard-restore`. **If that backup fails, no tables are replaced and the portal leaves maintenance mode.** If it succeeds, the portal removes the current views/tables, imports the selected SQL file, applies pending migrations for the installed code, and exits maintenance mode. A standalone completion page directs you to sign in with a restored account. The restore request uses temporary in-memory session storage, so it never saves its response into the tables being replaced. Failure messages also use a standalone page without database-dependent navigation.

Restoration replaces current tables; it does not merge individual records. All changes since the chosen snapshot disappear from the restored database, although the safety backup retains the pre-restore state. Historical academic-workflow upgrades can leave older results in draft and requiring the preparation/review steps documented in [Academic result management](academic-results.md).

## Failure and disaster recovery

An SQL restore is not transactional. If clearing/importing tables or applying migrations fails, the database may be partially restored. The portal deliberately remains in maintenance mode. The file-based application log records the selected backup and safety-backup filename. Have the server administrator recover into a separate database, verify the result and complete repairs before reopening access. Do not simply run `artisan up` over a partially restored database.

The dashboard is not an emergency login bypass. If account tables are empty, admin login is unavailable, or MySQL reports missing tablespaces, use server-level recovery. In particular, a broken table can prevent the mandatory safety backup; the web restore will stop rather than bypass that safeguard. Existing SQL backups and MySQL recovery logs should be preserved.

## Server requirements

- MySQL as the application's configured database connection.
- `mysqldump` and `mysql` executables available to the PHP web process.
- Database permissions needed to create/drop a temporary validation database, dump, drop and recreate the application's tables, restore routines/triggers where present, and run migrations.
- Writable private backup directory and Laravel storage directories.
- File-based Laravel maintenance mode (`APP_MAINTENANCE_DRIVER=file`).
- Adequate PHP/web-server request timeouts and disk space. This synchronous screen is intended for databases that fit within the server's request limits; large installations need a supervised server-side restore.

Optional environment settings:

```dotenv
DB_BACKUP_BINARY=mysqldump
DB_RESTORE_BINARY=mysql
DB_BACKUP_TIMEOUT=900
```

Database passwords are supplied to subprocesses through the environment, not command-line arguments. A filesystem lock serializes operations started through this dashboard. Coordinate separate CLI/scheduled operations before restoring; the dashboard lock does not stop external database writers or the existing scheduled command.

Access, input validation, safe file lookup, dump completion, concurrency, replication-state filtering, missing-session-table handling, and restore sequencing are covered by isolated SQLite tests. MySQL preflight should also be verified on the deployment server. Do not run destructive restore tests against the portal database.
