# Institution integrations and operations (steps 10–12)

Implemented 18 October 2026. This runbook covers the shared development platform and production configuration templates. It does not authorize production cutover or migration of the existing school; that remains step 14.

## Institution integrations

School administrators open **Payment Integration** in their portal and confirm their current password to save an institution-owned Paystack secret. Credentials use Laravel encrypted casts under APP_KEY; responses, old-input, and audit records do not expose keys. Back up APP_KEY separately and restrict access. A tenant never falls back to the platform subscription key or legacy school key. Configure the platform subscription key separately through PLATFORM_PAYSTACK_SECRET_KEY.

Each institution registers the webhook and callback shown in its portal. Host resolution selects the institution before signature validation. Provider verification also checks the existing payment reference, amount, currency and payer. External gateway identifiers can overlap between schools. Signed webhook processing and financial reconciliation continue for suspended, archived or expired institutions so previously collected payments can settle; these paths do not reopen portal access. Duplicate callbacks retain existing idempotency controls.

Rotate keys within the same Paystack business account: the two preceding signature keys remain valid for up to seven days. API verification uses the current key. Changing business accounts requires operator reconciliation of outstanding payments before switching. No credentials or real transactions were used in automated acceptance.

Mail uses the shared operator transport and institution sender name and links; essential messages remain available across tiers. School-specific SMTP accounts are not provided. Firebase has no reviewed institution ownership model: Firebase resolution and existing shared Firebase endpoints are denied in institution context. The legacy application keeps its existing integration behavior. Context exit restores configuration, mail managers and log context.

## Workers, scheduler and monitoring

Use QUEUE_CONNECTION=database for asynchronous production work. Institutional payloads carry institution_id, use the central jobs table with tenant_id, and target institution_UUID queues through the institutions connection. A normal database connection cannot accept school jobs from an active tenant context. Context is entered before model deserialization and closed after success, exception or cancellation. Missing or mismatched identities are discarded before running the handler.

Run these under the application identity:

~~~sh
php artisan tenancy:dispatch
php artisan tenancy:work --max-jobs=100
php artisan schedule:run
~~~

Install one scheduler cron entry and supervised institution workers from deploy/tenancy. The worker refreshes its queue list by exiting after 300 seconds or 100 jobs; the supervisor restarts it. On Linux install pcntl for enforced timeouts. Windows development does not provide the same process timeout guarantee. Existing legacy and platform queue workloads require their own workers when asynchronous.

The dispatcher claims unique institution/task/local-time slots: heartbeat and subscription status each minute, tuition generation every 15 minutes, payment reconciliation every 10 minutes, reminders daily after 08:00 local time, settlement sync daily after 06:00. Gateway tasks require that institution's key. Billing and reminders require portal eligibility and plan access. Ordinary pending work is cancelled on suspension/archival; financial follow-up remains eligible. Failed/stale claims retry at most three attempts after five minutes. The worker uses 120-second execution timeout, 180-second retry visibility and 60-second retry backoff. Pending institutional queue quota is 1,000 records per institution.

Platform institution details show recent task runs, health and lifecycle audit events. Application logs include institution_id during the context. No payment keys are logged. Monitor failures, missing scheduler heartbeat, stalled restores, backup failures and growing queue counts. Explicitly review failed jobs before requeueing; handlers still enforce current ownership and lifecycle.

Institution operations take a per-school mutex (MySQL GET_LOCK across processes; SQLite filesystem lock for development). This serializes that school's requests/jobs against lifecycle changes and restores; another school can continue. A five-second lock wait can produce HTTP 503 under contention. This conservative throughput tradeoff should be measured before a high-volume launch; it is not a claim of parallel requests within a school. Shared maintenance blocks institution HTTP/context entry and dispatcher work.

## Institution exports and restoration

School administrators use **School Exports & Restore** and confirm their current password. Archives contain only owned business rows, institutional profile/settings, and private/public local files. They exclude shared tables, other schools, gateway credentials, sessions, personal access tokens, reset tokens, queue infrastructure, health and central subscription invoices. Cloud/S3 files are outside this local archive format. Whole-database backup endpoints remain denied to schools.

Archives are private ZIP files, bound to their central UUID, institution and SHA-256 checksum. File entries, hashes, sizes, schema, owned links and global-ID collisions are checked before restoration. Quotas are 100,000 rows, 64 MiB uncompressed content and five ready exports per institution. Expiry is seven days; the daily tenancy:prune-exports schedule deletes only expired archive files and records their deletion. Institution records are retained. These archives are not encrypted independently: protect filesystem access, storage encryption and offline copies. Password hashes and personal data are present; restrict downloaded copies accordingly.

Restoration requires the typed archive confirmation and acknowledgement, validates the current administrator remains an active administrator in the snapshot, and creates a safety archive first. It replaces only the selected institution's business rows and local files. Current passwords, roles, deactivation and security settings for existing accounts are preserved; restored missing accounts receive unusable random passwords and require account recovery. Sessions, remember tokens, access tokens and queued school work are revoked. The initiating administrator must sign in again.

Self-service restore is refused if financial records changed after the export. This prevents silent rewinding of received payments, invoices or financial approvals; ask an operator for a reviewed selective recovery instead. Archives from another schema version cannot be restored through the school UI. Export quotas also apply to safety copies: delete an unneeded own export if the quota prevents restoration.

A restore journal records prepared/committed state while the institution is marked restoring. On ordinary errors, rows and files roll back. If the process dies, keep the school blocked, inspect the journal and use:

~~~sh
php artisan tenancy:recover-restore school-slug --reason="Investigated interrupted restore and verified recovery state"
~~~

Recovery uses the journal and staged directories to roll back an uncommitted file activation before re-enabling the prior status. Never manually delete staged .restore-* directories while an institution is restoring. If the database commit completed, its journal is committed atomically and the restored state is authoritative; leftover staged files can be inspected and removed by an operator after verification. Maintain independent full database AND storage backups for disaster recovery.

## Lifecycle policy

Platform administrators may suspend, reactivate, archive and replace the primary institution hostname; every action requires a reason. Domain replacement requires operator verification of school authorization, DNS and HTTPS readiness, rejects collisions/reserved central domains and revokes existing sessions/tokens and pending onboarding handoffs. Update the school payment provider webhook/callback and published links after replacement; the old hostname is removed. Both a single subdomain beneath INSTITUTION_BASE_DOMAIN and an independently owned school hostname such as academics.mudiameuniversity.edu.ng are supported. DNS ownership challenges, DNS provisioning and certificate issuance are not automated. See [domain routing and deployment](domain-routing.md).

Suspension and archival block normal access, revoke queued ordinary work and credentials, and retain school records and subscription history. Archival is the chosen retention policy: there is no automatic permanent institution deletion, no automatic refund and no configured legal retention duration. A permanent deletion request needs a separately approved retention and financial-record procedure. Central billing/history access remains separate from portal access.

## Deployment configuration

Templates in deploy/tenancy cover Nginx HTTPS, supervisor, scheduler and least-privilege MySQL users. Replace example domains, certificate paths, PHP socket, release path and secret placeholders before installation. Provide apex and wildcard DNS, an apex-plus-wildcard certificate, unknown-host rejection at the proxy, and HTTPS before collecting credentials.

Set APP_ENV=production, APP_DEBUG=false, APP_URL and PLATFORM_URL to the central HTTPS origin, CENTRAL_DOMAINS to its hostname, INSTITUTION_BASE_DOMAIN to the institution suffix, SESSION_SECURE_COOKIE=true and SESSION_DOMAIN empty. TENANCY_LEGACY_HOSTS must name only the deliberately retained legacy host; do not make the legacy database the shared database. CENTRAL_DB_* runtime credentials need only SELECT/INSERT/UPDATE/DELETE on the shared schema. DDL credentials belong only in the operator upgrade environment as TENANCY_MIGRATION_USERNAME/PASSWORD. APP_KEY, provider keys and database credentials must not be committed.

Make storage and bootstrap/cache writable by the application user, with private tenant/export/backup roots inaccessible to the web server's static locations. Persist these directories across releases. Do not expose the public/storage symlink for the shared site. On multiple nodes use shared durable storage for institution files, exports and the maintenance marker, with consistent APP_KEY; MySQL named locks coordinate institution operations across nodes. Runtime isolation still depends on application scoping: a shared MySQL identity is not database-enforced row-level security. Monitor and validate that boundary continuously.

This repository supplies templates, not installed production DNS, certificates, accounts, storage encryption, offsite backups or supervised processes. Validate those on the target environment before launch.

## Shared schema upgrade and rollback

Migrations run globally once. Production tenancy:install refuses direct execution outside the supervised upgrade path. Do not run ordinary migrate against the shared database independently.

1. Rehearse the release and restore against a disposable database and copied storage. Record the current application release, APP_KEY backup and schema version.
2. Pause the platform/tenant ingress, all institutional and central writers, workers, scheduler and shared backup schedule on every node. Legacy writers may continue only while they have no access to the shared database. Verify processes are drained; --writers-paused is the operator's assertion, not a process manager.
3. Ensure backups and full storage copies are durable and free disk space is adequate. Run the upgrade under the dedicated operator environment:

~~~sh
php artisan tenancy:upgrade --writers-paused
~~~

The command takes a global upgrade lock, sets the shared maintenance marker, drains institution mutexes, creates and inspects a full shared SQL backup, runs migrations, records a schema-version hash and rollout outcome, and clears maintenance only on success. It never migrates the existing legacy school's database. Runtime credentials are restored afterward.

4. Inspect the platform_schema_rollouts row, verified SQL backup, pending migrations, HTTP/authentication, representative academic/financial records and both-school isolation. Restart workers and scheduler and restore ingress after validation. Preserve rollout backups externally; this command does not automatically expire them.
5. On failure, keep maintenance and writers paused. Inspect the rollout failure class and application logs. Choose a forward fix or restore the complete verified pre-upgrade SQL backup with the matching code and storage checkpoint under operator supervision. Tenant exports cannot reverse a shared schema upgrade. Restore into a separate database first and verify it before replacing the shared database; never point recovery at the legacy school's database.
6. If repairing forward, rerun tenancy:upgrade after review. If schema is already complete and verified, the operator can use tenancy:maintenance off --reason="Verified recovered schema and application release". That command refuses pending migrations. It cannot prove semantic compatibility or business correctness; the operator must verify these. Do not clear maintenance merely to bypass a failure.

MySQL DDL is not transactionally rolled back by this command. Once new writes resume, reverting the SQL backup loses those writes and requires a reviewed reconciliation plan. Preserve this last safe rollback point and treat later recovery as a separate incident.
