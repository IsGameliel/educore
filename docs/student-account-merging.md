# Student account merging

Administrators can open **Students → Merge duplicates**, search by name, email, or matric number, select 2–10 accounts, and choose one account to keep. Name matches are suggestions; the administrator must verify identity.

The preview shows account-level record counts and lets the administrator select the source of each profile field. Existing retained profile values are suggested first; empty fields are filled from another selected account when possible. The retained name, login email, password, and security settings remain unchanged.

Confirmation requires an identity checkbox and a written reason. The server stores the selection and a signed record fingerprint in the administrator's session. A changed account or record requires a fresh preview. Account and record rows are locked during the database transaction.

The service transfers student ownership columns (`user_id`, `student_id`, and activity log `target_user_id`) in the current database schema. Academic registrations, results and revisions, admission applications and their existing document links, attendance, test responses, student service requests, tuition invoices, payments, projects, tasks, team ownership, and memberships follow the retained account. Record IDs, payment references, historical result matric numbers, scores, invoice snapshots, files, and actor attribution remain intact. Enrollment observers are suppressed during the profile update so the merge does not initiate new billing.

Identical duplicate attendance rows, credit limits, course memberships, and team memberships can be consolidated when they have no incoming foreign references. Identical course registrations can also be consolidated: all enrollment fields must match, including course, session, semester, status, and carryover result. Record ID, student ID, creation/update timestamps, registration date, and actor may differ. The kept registration retains its original date and actor, and the removed records preserve their complete provenance in the audit archive. Matching missing sessions are supported. The registration on the retained account is preferred; otherwise the lowest record ID is kept. Linked results, including published and soft-deleted history, are retargeted without changing scores or publication status. Original result links and removed registration rows are saved in the merge audit. Unexpected incoming references or links from students outside the selection block consolidation.

The preview lists the records to keep and consolidate. Other overlapping records block the merge, including differing registrations, results, differing attendance or limits, and active tuition invoices for the same period or overlapping annual/semester billing. Review and resolve those records through the relevant academic or financial workflow, then refresh the preview. The merge workflow does not override published marks or reconcile conflicting charges.

Duplicate user rows are archived with `merged_into_id` and `merged_at`; they are not deleted. Normal User queries and authentication exclude archived accounts. Their sessions, API tokens, remember tokens, and password reset tokens are revoked. Their email addresses remain reserved. No merge can select an already archived account.

`student_account_merges` stores the administrator, reason, original profile snapshots, transferred records, and consolidated duplicate rows. Credentials are excluded from account snapshots. The dashboard displays recent merges, and an activity log records each merge. There is no automatic undo: restore requires the appropriate backup or a reviewed recovery using the audit history.

For other environments, apply `database/migrations/2026_10_05_000000_create_student_account_merges.php` before serving code that uses the archived-account User scope.

Run the merge feature tests against an isolated SQLite database. Do not use the live student database with `RefreshDatabase`.
