# Legacy data resolution — 18 October 2026

The owner requested resolution of the source-data issues and recovery or review of missing documents. Changes were applied to the existing local legacy database after application maintenance, a new SQL backup and an actual disposable restore verification. The shared development institutions were not changed by the resolution. Application maintenance was cleared after verification; this is not a production cutover.

## Applied changes

| Issue | Resolution |
| --- | --- |
| 99 personal teams with nonexistent owners | Archived original rows privately and removed them from active tables only after checking that no surviving records referenced them. |
| Two schedules referencing missing course 174 | Searched all available legacy SQL backups; no matching original course was found. Archived the invalid schedules with their dependent attendance session and attendance record. No replacement course or attendance was invented. |
| Two duplicate matric groups, four accounts | Released the stale matric claim on an already-merged account. Consolidated the remaining matching identity pair using the existing audited merge workflow, retaining the older account and its password. No account row was deleted. |
| Overlapping enrollment records | Consolidated 11 identical registrations and four identical academic-session histories. Full originals and their retained record IDs are preserved in the merge audit. Contradictory academic levels remain a blocking conflict. |
| 31 broken document references | Reviewed eight distinct original files: five photos and three PDFs referenced by 26 result fields. Workspace and ZIP-backup searches found no matches. Original-server requests recovered no valid files; requests returned access denial or connection failures. Preserved all original paths/URLs in the private archive and cleared the unavailable active references. Photos fall back to the existing default avatar. Transcripts require original-file recovery or reviewed reissuance; no valid document was fabricated. |

The merged source account remains archived and cannot authenticate. Its matric claim and old sessions/tokens were revoked. All 456 account rows remain stored. Existing credentials on the retained account are unchanged.

## Application fixes

StudentAccountMerge now consolidates identical student_academic_sessions rows while archiving originals, and always releases archived source matric claims, including when retained and source matric values already match. It still blocks differing academic levels, incompatible registrations, unknown incoming references and stale previews.

New operator command:

~~~sh
php artisan legacy:resolve-data
~~~

This previews a fingerprinted plan. Execution requires the exact reviewed digest, an active existing administrator audit actor, application maintenance and an assertion that writers are paused. The command creates a restore-verified SQL backup before applying its transaction. It refuses a shared platform or active tenant context. It is not an unattended recurring cleanup task.

All removed rows and original references are retained in a private resolution archive. The activity log links that archive's filename and SHA-256 checksum. Do not publish these archives; they contain personal data. A pre-change backup is available for recovery. Once new writes resume, do not blindly restore it over newer work; perform reviewed selective recovery or reconcile a restored copy first.

## Verification

- Merge regression: 21 passed, 104 assertions.
- Resolution tests: four passed, 23 assertions; cover successful archival/merge, changed or different identities, unauthorized actors and shared-platform refusal.
- Thirteen comparisons against a separately restored pre-change backup passed. Financial tables, result identities/scores/states/academic links, all account IDs/passwords/roles/deactivation flags were preserved. Consolidated originals are fully accounted for.
- Fresh source preflight: zero orphan/missing-course blockers, zero duplicate matric groups, zero active missing-file references. The two remaining active document references have local files.
- Actual-data migration and scoped restore rehearsal passed without quarantine or missing-document exceptions. It transferred 15,645 business rows, including 456 stored accounts, 58 results and two payments, plus 18 checked files. Another institution remained unchanged. Its disposable databases and temporary storage were removed.

Private evidence:

- Resolution/backup/verification: storage/app/backups/legacy-resolutions/20261018-095933-9efcf667-2192-40ec-89df-0c068551104a/.
- Original-file recovery attempts: storage/app/backups/legacy-document-recovery/20261018-095602-2900bd3d-e1e2-4f5b-8dea-863521d32f83/.
- Clean migration rehearsal: storage/app/backups/migration-rehearsals/educore_migration_20261018_bbea91dc8c28/.

## Remaining distinction

The stale database references have been resolved by reviewed archival, not by recovering the absent originals. Keep the private manifest for later file recovery or reupload. Hosting, production configuration, actual gateway tests, cutover and live monitoring remain separate launch requirements. The migration must carry forward this review evidence and retained archives; clearing an unavailable reference does not establish that a historical PDF was valid or recovered.
