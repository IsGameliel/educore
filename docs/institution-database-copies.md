# Institution database copies

The shared platform database remains authoritative. School requests and background business operations continue to use tenant-scoped shared records. Each configured school can additionally receive a one-way MySQL snapshot in its own dedicated database on the same MySQL server. There is no reverse synchronization and no failover into the copy. This does not replace tenant isolation or historical backups.

## Owner workflow

1. In the hosting panel, create a new empty database for the school, such as `euvionco_mudiame_copy`, and a dedicated user. Grant that user SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER and INDEX on that database only. Atomic table replacement needs schema privileges. Do not reuse the shared/support runtime user, grant global privileges or let the copy user read other schools' databases.
2. Apply the central migration using the supervised shared upgrade process with SQL/file backups, paused writers/workers and migration credentials. Rehearse on a disposable database first. New code must not activate ahead of the migration.
3. As platform Owner, open Institutions, select the institution, then Institution database copy. Supply its database name, username and password, an audit reason and the dedicated-target confirmation. Platform password confirmation is required for sensitive changes. Other platform roles and school users cannot configure copies or see their credentials.
4. The scheduler queues enabled copies about every ten minutes (`tenancy:copies-dispatch` is checked every five minutes, with a ten-minute claim interval). Jobs use the central jobs table/default queue, with three attempts and backoff. The existing central worker must read that table; on cPanel use DB_QUEUE_CONNECTION=central and the prepared central-worker cron. Never run these jobs inside a TenantContext.
5. Queue synchronization now for an initial run or let the scheduler dispatch it. The institution detail page displays status, last verified snapshot, row count and a sanitized failure stage. A timestamp older than twenty minutes is flagged as delayed.
6. Pause synchronization to retain the last copy without scheduling new ones. Pausing/changing configuration during a run prevents the old job from reporting success for the new configuration. A change that races the final activation can finish the already-started old snapshot; it never writes into another school's database.

Passwords are encrypted with the application APP_KEY and hidden from rendered pages, JSON and queue payloads. Preserve APP_KEY with private backups. Connection settings are stored in the central-only institution_database_copies table. No hosting API credentials are required: database creation is a hosting-operator step. New school registration continues to work before a copy is configured.

## Deploying onto the existing hosting installation

Do not run tenancy:bootstrap, migrate:fresh or key:generate again. Preserve the existing APP_KEY and shared data. First pull and prepare the new release while the current website remains selected:

```sh
cd ~/repos/educore
git pull --ff-only origin main
source ~/.config/euvion/deploy.env
SHA=$(git rev-parse HEAD)
bash deploy/cpanel/deploy.sh "$SHA" --prepare
```

Confirm the database server is MySQL 8+ or MariaDB 10.6.1+ before enabling copies. Ensure the configured MySQL dump executable works, retain independent SQL/file backups, then pause all scheduler/worker cron entries and wait for in-flight writers to finish. The following upgrade uses the existing verified-backup workflow. Enter migration credentials privately; they need schema/backup privileges on the shared database. If any command fails, keep writers paused and inspect the failure before continuing:

```sh
(
set -e
source ~/.config/euvion/deploy.env
cd ~/repos/educore
SHA=$(git rev-parse HEAD)
cd "$APP_ROOT/releases/$SHA"
"$PHP_BIN" artisan down
"$PHP_BIN" artisan config:clear
read -r -p 'Shared migration username: ' TENANCY_MIGRATION_USERNAME
read -r -s -p 'Shared migration password: ' TENANCY_MIGRATION_PASSWORD
printf '\n'
export TENANCY_MIGRATION_USERNAME TENANCY_MIGRATION_PASSWORD
"$PHP_BIN" artisan tenancy:upgrade --writers-paused
unset TENANCY_MIGRATION_USERNAME TENANCY_MIGRATION_PASSWORD
cd ~/repos/educore
bash deploy/cpanel/deploy.sh "$SHA"
cd "$APP_ROOT/current"
"$PHP_BIN" artisan up
)
```

Check the website and institution pages, then resume the scheduler and central/institution workers. Configure the new dedicated copy databases through the owner workflow above. Leave copies paused until their individual verified snapshot succeeds. A GitHub deployment that encounters the new pending central migration should stop activation; do not bypass that check. The support database has no additional migration for this feature.

## Snapshot behavior

The source must use MySQL/InnoDB. Production activation requires MySQL 8+ or MariaDB 10.6.1+; older versions are refused because crash-safe table renaming is required. Local/test rehearsal can exercise older servers but does not establish server-crash recovery. See [MySQL RENAME TABLE](https://dev.mysql.com/doc/refman/8.4/en/rename-table.html) and [MariaDB RENAME TABLE](https://mariadb.com/docs/server/reference/sql-statements/data-definition/rename-table). The target must initially be empty. Its ownership marker binds it to one institution and source database. Existing unrelated tables, central/support databases, reused source usernames and targets owned by another school are refused. The same target name cannot be assigned to two institutions through the owner form.

All source tables with tenant_id are copied using an explicit institution filter, except live sessions, password resets, API tokens, jobs and cache infrastructure. This includes students, staff, courses, results, academic settings, school-level finance records and uploaded-document references. Central platform accounts, subscription provider credentials/payments, plans and other institutions are not copied. Binary uploaded files remain in the existing isolated storage; this feature copies database records, not file contents. User credential hashes and encrypted school account fields are sensitive data and remain in the school copy, so do not expose copy credentials to students or third parties.

A repeatable-read source transaction provides a consistent snapshot without moving primary writes into another database. Deterministic staging tables receive the selected rows. Counts and ordered row digests are verified before a single atomic MySQL RENAME TABLE activates the complete snapshot and ownership manifest. Old managed tables are then cleaned up. New/deleted columns and tables are rebuilt from the current source schema. Source indexes/types are preserved; foreign keys are intentionally omitted from the analytical copy because central and cross-table dependencies must not point back at the platform. The copy is not an independently writable application database.

Failed runs preserve the previous activated snapshot. Staging cleanup is recorded in the ownership marker and can be retried after interruption. Unknown target tables are never silently deleted. Updates and deletions appear on the next successful snapshot; changes made directly in the copy are overwritten. Records created and deleted between snapshots are not a historical log. If connection validation fails before a target is opened, no target is modified.

## Limits and recovery

This release uses full snapshots, not per-row streaming replication. The default limit is 100,000 copied rows per school per run, 250 rows per batch, and a 120-second queue timeout. Large schools, large individual rows, schema changes during a run and restrictive hosting quotas can fail a run. Increase capacity only after measuring/rehearsing; leave the copy paused if the host cannot support its volume. Primary records remain in the shared database regardless of copy failure. Keep the central scheduler/worker logs and queue failure counts under observation.

Ownership errors require operator investigation, not bypassing the marker. Never connect this feature to an existing authoritative institution database. If a managed copy is compromised or cannot be recovered, pause it, preserve its database/marker and audit evidence, create a new empty database/user, then reconfigure and verify a fresh snapshot. Restoring a copy back into the shared platform is a separate supervised operation and is not provided here. Retain off-host historical SQL and file backups.

## Acceptance

Use two disposable schools/databases. Verify school A never receives B's records, platform/security/queue tables are absent, credentials are encrypted and never flashed, non-owner routes are rejected, repeated submissions/jobs do not create duplicates, edits/deletions/schema changes appear on retry, bad target credentials/foreign tables/ownership mismatches are refused, and an interrupted or over-limit run preserves the last verified copy. Also verify the dedicated target user cannot query the shared database. Do not activate real copies until this rehearsal and the production schema upgrade have passed.

For dedicated primary databases and central snapshots, see [Dedicated institution databases](dedicated-institution-databases.md). The outbound copy configuration described above remains available for schools that have not been switched.
