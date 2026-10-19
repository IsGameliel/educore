# Dedicated institution databases with a central copy

Each activated institution uses its own MySQL database for students, staff, courses, results, exams and other academic records. Euvion retains a verified, delayed copy inside `euvionco_euvion_shared`. Platform accounts, subscription invoices, provider verification, administrator permissions and queues remain central.

Institutions manage records through their existing school dashboards. Hosting credentials and the DirectAdmin login key are operator-only. This release does not give school users access to your hosting account or phpMyAdmin.

## Central copy format

Independent school databases can both contain student ID 1. Euvion therefore stores dedicated-school snapshots in `institution_record_snapshots`, keyed by institution, source table and row number. Each payload is a lossless base64-encoded PHP scalar-array serialization; it includes original local IDs, relationships and binary values. Readers must disable object instantiation (`allowed_classes => false`). Copies include students, courses, results, staff and academic records. They exclude live sessions, reset/API tokens, jobs and cache data.

The old normalized shared school rows remain as pre-switch records; they are not the current copy after activation. Do not query those old rows as current academic data. Platform student/staff counters and operation details use the latest verified central snapshot. The institution dashboard always uses its dedicated primary. Shared-mode institutions continue working during the transition.

Snapshots replace one institution's central records in a single transaction after checksums and counts pass. A failure retains its previous complete snapshot. Deletions replicate on the next successful run. This is not a historical backup, automatic failover, bidirectional replication or an independently writable second portal. Upload files remain in the existing isolated school storage; database copies retain their file references.

Default snapshot quotas are 100,000 rows and 64 MiB encoded payload per school. Central jobs have a 120-second limit. Large institutions need capacity testing and revised workers before onboarding. Copies normally run about every ten minutes. Monitor delayed/failed copies and central worker logs.

## Deployment of this change

1. Keep `DIRECTADMIN_DATABASES_ENABLED=false`. Pull main in `~/repos/educore`, source `~/.config/euvion/deploy.env`, and prepare the new release:

   ```bash
   cd ~/repos/educore
   git pull --ff-only
   source ~/.config/euvion/deploy.env
   SHA=$(git rev-parse HEAD)
   bash deploy/cpanel/deploy.sh "$SHA" --prepare
   ```

2. Pause the scheduler and queue cron jobs and wait for in-flight jobs. In the prepared release, run `artisan down`, `artisan config:clear`, then the existing verified `tenancy:upgrade --writers-paused` procedure with the shared database migration credentials entered privately. This migration adds primary-direction metadata and the central snapshot table. Never bootstrap, migrate:fresh or regenerate APP_KEY on this installation.
3. Activate with `bash deploy/cpanel/deploy.sh "$SHA"` from the control repository, run `~/euvion/current/artisan up` using `/usr/local/php83/bin/php`, verify platform login and existing portals, then resume cron.
4. Preserve the shared `.env`, APP_KEY, storage and all verified SQL backups. No institution switches database simply by deploying this release.

## Connect DirectAdmin

Automatic creation uses DirectAdmin's documented `CMD_API_DATABASES` list/create API. It only creates a new database/user and verifies its restricted connection. It never deletes databases, rotates an existing user's password, or grants access to your shared database.

Create a dedicated Login Key in DirectAdmin for account `euvionco`. Allow `CMD_API_DATABASES`; do not allow browser/HTM login, ALL_USER or login-key administration. Restrict the key to your server's outbound IP as confirmed by the hosting provider. The key must be permitted to list and create databases within the hosting package's quota. The hosting account's broad database API permission is still sensitive; keep it private.

Edit `/home/euvionco/euvion/shared/.env` privately:

```dotenv
DIRECTADMIN_DATABASES_ENABLED=false
DIRECTADMIN_URL=https://www.euvion.com.ng:2222
DIRECTADMIN_USERNAME=euvionco
DIRECTADMIN_LOGIN_KEY=YOUR_PRIVATE_LOGIN_KEY
```

Use the real HTTPS panel hostname with a valid certificate, not a URL containing login credentials. Do not paste the key into chat or commit it to GitHub. Keep the `.env` permissions at 600.

Refresh configuration and perform the read-only preflight:

```bash
cd ~/euvion/current
/usr/local/php83/bin/php artisan config:cache
/usr/local/php83/bin/php artisan tenancy:hosting-check
```

This verifies authentication/listing and requires MySQL 8+ or MariaDB 10.6.1+ for crash-safe initial snapshots. It does not prove that your package permits creation; the first disposable online test must verify that as well.

After preflight passes, set `DIRECTADMIN_DATABASES_ENABLED=true`, run `artisan config:cache` again, and ensure the central queue worker and scheduler cron jobs are running. The flag applies to new self-service signups. Existing schools require the supervised switch below.

## New institution flow

1. Signup saves the institution account, invoice and initial administrator in shared staging records. The new school is marked as requiring a dedicated database; academic access stays closed until preparation succeeds.
2. Verified provider payment retains the existing amount/currency/reference/customer checks. Only confirmed payment opens the paid setup path. A central job is queued after commit; the scheduler recovers a missed dispatch.
3. The worker creates a unique database and dedicated user through DirectAdmin, with generated credentials encrypted under APP_KEY. It verifies the new connection and empty schema. Retries preserve the same provisioning intent and credentials; an unrecognized existing database is refused.
4. An institution lock prevents concurrent school writes. The worker takes a verified shared backup, copies only this institution's school records, checks row counts and checksums, creates empty local session/token/cache tables and promotes the connection.
5. A verified central snapshot is published. Branding onboarding and the institution dashboard become available on its approved hostname. Provisioning failure leaves payment verified and displays setup as pending; inspect the institution database status and failed central jobs.
6. Later academic writes go to its primary database. Snapshot failures are retried without redirecting school requests to stale shared records. Euvion still depends on central metadata/auth/queue availability; this is not offline operation when the whole central service fails.

## Switch an existing institution

1. Create an empty MySQL database and dedicated user in DirectAdmin, or retain its existing verified copy database. Grant that user privileges on that database only. Do not share credentials here.
2. As platform owner, go to Institutions > institution > Institution database copy and primary connection. Configure the connection with a reason and confirmation. Do not point it to an independently populated school database.
3. Pause workers/scheduler, wait for writers to finish, and put the application in maintenance. Record the institution UUID from its platform page URL.
4. Run from the active release:

   ```bash
   /usr/local/php83/bin/php artisan tenancy:database-activate INSTITUTION_UUID --writers-paused
   ```

   A verified shared backup is required. An unavailable backup utility, wrong connection, unmanaged target, checksum mismatch or missing permissions prevents activation. The owner password hashes and record IDs are retained. Academic foreign keys present in the shared schema are restored within the dedicated database; references to central institution metadata are excluded. Local sessions start empty and remember tokens are revoked. Repeating a completed activation is safe.
5. On success run `artisan tenancy:copies-dispatch`, `artisan up`, resume cron, then check the first central copy. Test the school login, students, courses, results, staff, uploaded files, account settings and background jobs. Confirm the database section says Dedicated primary database and has a recent verified copy.
6. Never reverse the direction by changing the copy form. Primary connection replacement and recovery are supervised operator procedures. Pausing synchronization does not change the primary or remove its data. Owners can resume central copies from the same page.

If activation is interrupted, keep writers paused and maintenance enabled. The target ownership marker prevents an old outbound job from overwriting a promoted database. Retry performs count/checksum verification before completing an interrupted promotion. A mismatch needs operator inspection; do not erase the marker or rerun bootstrap.

## Subsequent upgrades and backups

Deployments run `tenancy:databases-check` and stop if any dedicated schema is behind the release. While writers are paused and maintenance enabled, prepare the new release, upgrade shared schema, then run for each dedicated school:

```bash
/usr/local/php83/bin/php artisan tenancy:database-upgrade INSTITUTION_UUID --writers-paused
/usr/local/php83/bin/php artisan tenancy:databases-check
```

Each dedicated upgrade verifies ownership, takes a verified backup and runs only academic/tenant migration paths, never central platform migrations. New migrations must support dedicated schemas (no foreign keys into absent central tables). Do not activate a release until all checks pass. A migration failure keeps maintenance in place and requires inspection of its backup.

Nightly `tenancy:databases-backup` creates independent SQL backups under `shared/storage/app/backups/dedicated-institutions/INSTITUTION_UUID`. Check scheduler/backup logs and configure independent off-host retention for SQL and school files. A failed school backup returns a nonzero command status while other schools are attempted. Existing whole-platform restores remain supervised. School self-service restore is blocked for dedicated primaries until a coordinated dedicated recovery workflow is designed; school exports still read their primary.

## Acceptance checklist

- New verified paid signup receives a distinct database and restricted user; unpaid signup creates no hosting database.
- Its portal waits for setup and approved DNS/HTTPS, then opens branding onboarding and its own dashboard.
- Two schools can independently create the same numeric student ID without conflicts in Euvion's central copies.
- Changes and deletions appear in the next verified central snapshot; restarting/repeating jobs does not recreate active primaries.
- Failed copying retains the last complete central snapshot and does not undo primary writes.
- An unavailable or wrong school database never falls back to shared academic rows.
- Owner-only connection controls, recent password confirmation, reason validation, pause/resume auditing and secret redaction remain enforced.
- Academic background jobs, session revocation, school exports, profile changes and database backups use the correct primary.
- DirectAdmin quota/permission/TLS failures show setup failure without losing verified payment or deleting any database.

Local verification uses disposable MySQL databases and restricted users. DirectAdmin HTTP is simulated while the resulting schemas, worker jobs, snapshots and upgrade backups are real. Production API/hosting limits require the controlled online test above.

API references: [DirectAdmin database list/create](https://docs.directadmin.com/changelog/version-1.21.2.html#api-for-databases-new), [Login Key permissions](https://docs.directadmin.com/directadmin/general-usage/securing-da-panel.html), [API access](https://docs.directadmin.com/developer/api/).
