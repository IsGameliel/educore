# Euvion launch and existing-school cutover

**Resolution update — 18 October 2026:** the listed legacy issues were subsequently resolved through audited archival and account consolidation. A fresh migration passed without quarantine. Missing originals remain unavailable; their stale references were reviewed and archived. See [resolution report](legacy-data-resolution-2026-10-18.md). Earlier rehearsal findings below are historical evidence.

Target domain: **euvion.com.ng**, with school portals beneath **INSTITUTION.euvion.com.ng**. Hosting is not provisioned, so no production deployment or live cutover has occurred.

The owner delegated the existing school's first-semester defaults. Chosen terms: **Silver**, **1 October 2026–31 January 2027**, **complimentary migration semester**. No historical subscription debt is created. Renewals use normal standard-rate semester billing. Confirm the actual enrolled count at cutover. These terms are recorded in deploy/euvion/launch-plan.json; the deployment files contain no production secrets.

## Prepared deliverables

- Domain-specific Nginx HTTPS, supervisor, cron and environment templates in deploy/euvion; least-privilege database grants in deploy/tenancy.
- An operator snapshot importer with explicit source/target separation, ID/FK/polymorphic/CBT/transcript reference mapping, credential preservation and checked file copies.
- A read-only preflight audit for missing relationships and conflicting student identifiers; imported schools remain blocked for review.
- Explicit audited activation using an agreed no-charge semester invoice; no gateway transaction is fabricated and no automatic charge is attempted.
- Disposable actual-data rehearsal at tests/Manual/RehearseLegacyMigration.php, with a second institution already present to exercise ID collisions and isolation.

## Rehearsal and source-data review

The original source snapshot has pre-existing orphan personal teams, two schedules referencing missing course 174, associated attendance history, and two conflicting matriculation numbers affecting four accounts. Historical activity logs also refer to deleted subjects. Preserve the original verified SQL backup and private preflight/quarantine files.

The rehearsal-only quarantine helper is hard-restricted to newly generated disposable source database names. It archives orphan personal teams and invalid schedules with their dependent attendance records. It archives conflicting matric values and clears those fields only in the disposable copy, preserving all user accounts. It refuses to archive other referenced records or conflicting login emails. The live database is unchanged. These are not approved production data corrections.

The subsequent owner-requested source correction is complete; see the resolution report linked above. Before cutover, verify and retain its private archives and audit evidence. Do not quietly delete a student's records, choose a matric number owner, resurrect a missing course or discard attendance. The production importer still refuses unresolved active references and constraints.

Historical deleted activity subjects are retained as _legacy_subject metadata, with the dangling live link cleared. Historical result/merge JSON keeps its original source IDs as audit evidence; migration-report.json records the old/new maps. Operational CBT answer keys, attempt snapshots and transcript result-version maps are remapped. Do not interpret a source audit ID as a new live ID without its map. Sessions, remember tokens, reset tokens, API tokens, pending jobs and OTP challenges are not carried over. Keep the same securely retained APP_KEY for encrypted two-factor material; payment keys must be configured separately for the imported school's provider account.

The production importer refuses missing source document references; only an explicitly marked disposable rehearsal can continue to test the remaining transfer. Old transcript URLs without verified transcript-document metadata are preserved in the private migration report and cleared from live result links; reissue them through the reviewed workflow after recovering originals. This avoids forwarding users to the old school host or fabricating document validity.

The document audit found 31 missing references out of 33 checked (five profile-photo references and 26 transcript references). These may require the originals from the old host; the local captured files are insufficient for production acceptance.

Files under the source local/public disks are copied and checksummed. Existing shared tenant/export roots are excluded. S3 and files outside these roots need a separate transfer. Verify referenced documents and old links, not just file counts; capture any missing originals as launch blockers.

## Cutover sequence

1. Provision hosting with PHP compatible with composer.lock (project requires PHP ^8.2), required extensions, MySQL/MariaDB client tools, a process supervisor and durable private storage. Run composer check-platform-reqs there. Install the reviewed application release, not a development environment or backup directory.
2. Create apex/wildcard DNS and TLS for euvion.com.ng and school portals. Install the domain templates after replacing PHP socket and filesystem paths. Set runtime DB privileges separately from operator credentials. Preserve APP_KEY; do not generate a new key over migrated encrypted data. Configure mail and separate platform/school payment keys.
3. Leave public signup closed. Rehearse a fresh snapshot with the final source corrections and target configuration. Verify all active plus archived counts, credentials, relationships, document checksums, actual provider test-mode checkout/webhook/refund recovery, workers, permissions and scoped restore. Record approval of the private quarantine/source-data review before proceeding.
4. Agree a maintenance window after hosting and rehearsal acceptance. Notify affected users through the owner's approved channel. Pause source and shared-platform writers, scheduler and workers; retain the original school host as the rollback destination.
5. Take verified SQL AND file snapshots under paused writers; record checksums, schema and application release. Restore the source SQL into a separate snapshot connection and upgrade that copy. Never import directly from the running legacy database.
6. Install/upgrade the shared schema using the operator workflow in multi-tenancy-operations.md. Register a new pending institution using the correct school name, existing active administrator identity, chosen school subdomain, Silver and timezone. Do not provision a replacement administrator over imported credentials.
7. With all writers paused and shared maintenance enabled, review then execute:

~~~sh
php artisan tenancy:import-legacy existing-school --source=legacy_snapshot --files=/secure/source-files
php artisan tenancy:import-legacy existing-school --source=legacy_snapshot --files=/secure/source-files --execute --writers-paused --reason="Reviewed existing-school cutover from verified source snapshot"
~~~

The named connection must point to a separately restored copy and files must be the verified private/public snapshot roots. Keep any failed destination blocked for investigation; do not automatically retry over partial data. Verify counts, password hashes, retained deactivation, all relationships and file hashes from migration-report.json. Imported status does not grant portal access.

8. Apply the explicit agreed migration terms only after the import/data review. Replace ENROLLED_COUNT with the verified count:

~~~sh
php artisan tenancy:activate-import existing-school --plan=silver --semester="2026/2027 First Semester" --from=2026-10-01 --until=2027-01-31 --students=ENROLLED_COUNT --agreed-no-charge --reason="Owner delegated complimentary Silver migration semester; import reviewed"
~~~

The command creates an immutable zero-amount paid migration agreement, not a gateway payment. Dates still govern access. Existing academic/financial records remain retained, while Silver permissions apply to advanced modules. Central subscription renewal/account recovery setup needs the verified institution owner identity before billing access is opened; a newly reset central billing password must never overwrite the existing school administrator password.

9. Run the full acceptance checks, sign in using controlled school test accounts, verify sample students/results/payments/documents against the source, inspect logs and check another institution's isolation. Configure Paystack's new callback/webhook URLs before switching payment traffic. Clear shared maintenance only after schema and data review. Restart workers/scheduler, move school ingress and observe the first institution before enabling broader signup.
10. Observe scheduler/worker heartbeats, sign-in failures, authorization errors, payment reconciliation and backups. Record actual monitoring evidence and operator sign-off; templates and simulated responses do not establish live acceptance.

## Rollback and last safe point

Before enabling any new writes on the migrated school, the source remains authoritative and paused. Failed rehearsal is rolled back by dropping only the disposable databases this run created, after exact name validation, and deleting only its verified temporary storage root. Source SQL/storage and other schools remain unchanged.

During actual cutover before new writes: keep shared maintenance enabled, return ingress to the unchanged source release/host, resume source writers only after confirming no target writes occurred, and retain the failed destination/report for investigation. A failed shared-schema rollout uses its verified full shared SQL backup and matching code/storage checkpoint; restore into a separate database first. Never restore a whole shared backup while other institutions continue writing.

The **last safe rollback point is immediately before target writers/payment webhooks are enabled**. After target writes begin, a blind switch to the old database loses new registrations, results and payments. Pause writers on both sides, preserve both database/storage snapshots, reconcile provider transactions and business changes, and choose a reviewed forward recovery or replay plan. Never replay old queued payment jobs against an unreviewed destination.

## Remaining launch gates

Hosting, production credentials, DNS/TLS installation, owner/billing access setup, real provider verification, scheduled cutover and live monitoring remain incomplete. Legacy correction and missing-reference review are complete; preserve that evidence during migration. Original unavailable files still require old-host access or reupload if recovery is desired. This repository is prepared for those steps; it is not live on euvion.com.ng.
