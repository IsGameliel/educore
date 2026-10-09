# Private admissions, account deactivation, and student tests

## Deployment

1. Back up the database and uploaded files before deploying.
2. Pause new requests while deploying the code and applying `php artisan migrate`.
3. Run `php artisan admissions:privatize-documents`. This command copies every file under the public `admission-documents` folder to the private `local` disk, verifies its SHA-256 checksum, then removes its public source. Database paths do not change. Orphaned admission uploads are also moved. The command is safe to rerun and stops rather than overwriting a different private copy.
4. Check that public admission files are gone and an authorized administrator can still download existing documents. Private storage must be included in file backups.
5. Restart workers and resume requests after the checks pass.

There is no public-storage fallback for admission downloads. A document not yet moved is unavailable until migration completes. If files were previously exposed through a CDN or caching proxy, remove any cached copies there separately.

The schema migration stops if duplicate responses for the same student and test exist. Resolve those historical conflicts through an academic review; the migration does not guess which score to retain. Do not roll back the account-archive column on a database containing archived users: removing it would remove the sign-in exclusion for those accounts.

## Account deactivation

Student, staff, and self-service account removal now soft-delete the user. Password sign-in, session authentication, and API access are disabled; tokens are revoked. Academic results, course registrations, payments, admission documents, photos, and audit history remain. Historical record relationships can still display the archived user's identity. Active user lists exclude archived accounts, and the account-merging service rejects them.

The existing email and matriculation identifiers remain reserved. Reactivation or permanent removal requires a separate authorized institutional review; there is no automatic purge. Direct database deletes bypass these application protections and must not be used to deactivate accounts.

An admission payment already in progress can still settle after deactivation. Its financial and admission outcome is recorded without restoring account access or issuing new tuition invoices to the archived applicant.

## Student tests

Every start, answer, and submission request checks student role, test publication, department, and level. A started attempt stores randomized questions/options, grading data, answers, and its fixed server deadline in the database. Session changes cannot restart the timer or erase saved answers. Later edits to questions, marks, or duration do not alter the attempt snapshot.

Only options from the displayed snapshot question can be saved. At or after the deadline, the next request finalizes the previously saved answers; a late answer is ignored. Unanswered questions score zero. The browser submits when the deadline is reached, but if the browser is closed, finalization occurs on the next start/answer/submission request. No background exam-finalization worker is required.

Submission locks the student's row and attempt, calculates the score from the saved snapshot, and writes one response. Database unique constraints protect both attempts and responses. Repeated submissions return the existing result. Results have a GET endpoint scoped to the signed-in student and remain visible from the test list after publication closes.

Attempts started under the old session-only implementation cannot be safely reconstructed. Deploy between exams; old in-progress sessions will start a new database-backed attempt. Existing completed responses remain available. Administrative test deletion retains its existing behavior of removing the test, questions, attempts, and responses.
