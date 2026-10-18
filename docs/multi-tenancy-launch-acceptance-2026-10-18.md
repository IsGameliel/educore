# Steps 13–14 acceptance — 18 October 2026

**Resolution update — 18 October 2026:** the listed legacy issues were subsequently resolved through audited archival and account consolidation. A fresh migration passed without quarantine. Missing originals remain unavailable; their stale references were reviewed and archived. See [resolution report](legacy-data-resolution-2026-10-18.md). Earlier rehearsal findings below are historical evidence.

## Status

Step 13 is implemented as a staged commercial release, with explicit deferred specifications. Step 14's importer, agreed activation, copy rehearsal, rollback procedure and domain configuration are prepared. **No production launch or live-school cutover has occurred.** Hosting and source-data/document review remain blockers.

Owner-provided target: euvion.com.ng. The owner delegated semester defaults; selected complimentary Silver from 2026-10-01 to 2027-01-31. The actual school's database and original files were not changed. The no-charge agreement was exercised only in disposable rehearsal databases.

## Automated verification

- Full regression run: 526 passed, 2 skipped and one obsolete pricing-copy assertion failed because it expected Coming soon. The assertion was changed to the reviewed deferred-release wording.
- Final affected checks: six passed, 73 assertions, including that pricing regression, staged invoice scope, authenticated school-specific API capabilities, import/collision/credential preservation, missing-file production gate and scoped restore. The additional capability API case was added after the full-suite run. Across the full run and follow-ups, all 528 current executable cases have passing evidence; this is not a claim that a second full-suite invocation was performed.
- Disposable three-institution MySQL regression: 59 checks passed, including package billing, renewals, expiry, payment simulation, ownership, scheduled workers and restore/lifecycle behavior. No real provider transactions or external email were sent.
- Actual existing-school snapshot rehearsal: 34 checks passed after explicitly archiving source defects only in the disposable copy. It imported 15,657 business rows, including 456 active/archived accounts, 58 results and 2 payments, plus 18 checked files. It remapped an actual administrator ID collision, preserved its password hash, applied the agreed zero-charge Silver period, restored the imported school, and left another school's rows/files unchanged.
- Five native HTTP checks passed after the development catalogue rollout: pricing and school/legacy login returned 200, unauthenticated capability API returned 401, unknown school returned 404.
- Development catalogue rollout aa341fdf-e956-4b46-82d6-48a597139b45 created and inspected a shared SQL backup before migration, recorded success and cleared maintenance.

Final private rehearsal evidence: storage/app/backups/migration-rehearsals/educore_migration_20261018_0ada045c77a6/. It contains the original SQL backup, preflight, document audit, archived source rows and remapping report. These files contain personal data and must remain private. All databases and temporary storage created by the rehearsal were removed afterward.

## Source findings requiring review

| Finding | Rehearsal handling | Live requirement |
| --- | --- | --- |
| 99 orphan personal teams | Preserved in a private archive, removed only from disposable source | Review archival policy; no silent live deletion |
| Two schedules referencing missing course 174 | Preserved with one dependent attendance session and one attendance record | Correct the course or explicitly approve historical archival |
| Two duplicate matric groups affecting four accounts | Original values archived, conflicting fields cleared only in disposable source; accounts retained | Confirm each student's correct identifier |
| 340 activity subjects no longer present | Original subject type/ID retained in audit properties, dangling live links detached | Review historical handling; use old/new mapping report |
| 31 missing document references out of 33 checked | Reported; explicit disposable rehearsal continued so remaining transfer could be tested | Recover originals or approve a separate reviewed resolution; production import refuses missing files |
| 26 old transcript URLs without verified document metadata | Original URLs preserved in private migration report; active links cleared in rehearsal | Recover/reissue through reviewed workflow; do not fabricate document validity |

Missing-reference totals count database references, not necessarily distinct files. Five references are profile photos and 26 are transcripts associated with the old host. The 18 available local files were copied and restored with checksum verification; that does not establish complete source storage.

## Delivery and remaining gates

See [commercial module audit](commercial-module-audit.md) and [Euvion cutover runbook](euvion-launch-and-cutover.md). Domain configuration is in deploy/euvion. The deployment kit contains templates/runbooks only, no secrets or school records; deploy a separately reviewed application release.

Remaining: provision hosting, install runtime/operator credentials and DNS/TLS, resolve source findings and missing originals, configure institution billing-owner access, verify actual Paystack test-mode checkout/webhooks, schedule the cutover and observe the first live school. Keep broader signup closed until that acceptance is recorded. Deferred mobile/HR/assignments/wallet/campaign/automatic-timetable modules remain excluded from this release.
