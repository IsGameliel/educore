# Tuition billing

## First-time setup

1. Sign in as an admin and open **Tuition Fee Schedules** (`/finance/schedules`).
2. Verify student department, level and entry year in **Manage Students**. Entry year determines new versus returning students for the session. Missing/future entry years are not guessed and cannot be automatically invoiced.
3. Create a draft schedule for each session, department, level, student category and billing period. Add fee breakdown items in NGN. Amounts are stored as integer kobo.
4. Select **Annual**, **First** or **Second** billing. Annual and semester billing cannot overlap for the same cohort. For annual installments, choose a first-semester percentage and both deadlines. Default 100% requires full payment. Semester invoices require their full balance for that semester.
5. Review and publish the fees. Publication is admin-only. Admins can edit published fees, deadlines and payment policy; changes apply to future invoices only. Once invoices exist, the cohort and billing period are fixed. Matching active-session students receive unique invoices automatically. Publish both semester schedules if choosing semester billing.
6. Select **Enable tuition clearance** for the session when all required fees have been published. For the active session, missing invoice coverage blocks activation. Existing sessions start with clearance disabled; no tuition amounts or invoices are fabricated by the migration.

Published schedules generate invoices only for the active session. Session activation requires a preview and explicit approval of selected eligible student promotions; activation alone and renaming never change student levels. Missing invoices are generated after the approved changes. New students receive matching invoices after their application payment is verified. The tuition page, dashboard and registration check also generate any missing current-session invoices. **Generate missing invoices** lets bursary staff refresh coverage after imports or enrollment fixes.

New/returning classification uses entry year equal to the academic session's start year, otherwise returning. A session's first invoice freezes that student's department, level and category for any later semester invoice. Issued invoice amounts and dates are immutable. Billed sessions cannot be renamed. Use explicit adjustments for approved changes to an existing invoice.

Use **Edit** or **Delete** beside a schedule in **Tuition fee schedules**. Deleting removes the schedule from the list and stops new invoices from it. Its invoices disappear from the student's active tuition list and dashboard totals, and the application blocks new or resumed checkout. Issued invoices, payments, receipts and audit history remain available as records. Unpaid withdrawn invoices require bursary review for clearance; already-paid clearance is retained. Previously opened gateway checkouts may still settle and are verified normally. You can create a replacement for the same cohort. Existing students retain their original invoices and are not billed again for an overlapping annual/semester period.

## Student flow

For reusable templates, enrollment triggers, reminder delivery and explicit withdrawn-invoice decisions, see [Finance automation](finance-automation.md). An admin can explicitly replace an untouched withdrawn invoice; automatic generation never replaces it silently.

**Fees & Payments → Tuition & Balance → Invoice → Paystack → Verified payment → Receipt / registration clearance.**

The dashboard shows current-session tuition, payments, balance and both semester clearance states. **Payment History** includes tuition and the existing application, transcript and appeal fees. **Receipts** downloads verified payments as PDFs.

Annual installment clearance requires the configured percentage of adjusted tuition for First semester and 100% cumulatively for Second semester. Scholarships/waivers reduce adjusted tuition; completed refunds reduce net payments and can reopen a balance. Deadline checks use the application's date/timezone and mark unpaid required amounts overdue after the deadline.

Clearance is checked server-side for both student and admin course registration. A session with clearance enabled and no matching invoice is blocked with a bursary message. Students retain login, billing and other existing portal access. The system does not remove already registered courses if a refund or exemption expiry later changes clearance.

Admins can open **Registration Settings** from the dashboard or Academic Setup menu. **Course registration is open** controls whether students can submit new course registrations. **Block registration when required semester fees are unpaid** enables or bypasses tuition checks for student submissions. Both default to on. The fee check uses issued annual or matching-semester tuition bills, configured installment percentages, waivers and approved exemptions. It applies to issued bills even if the session's older tuition switch is off; an unbilled session without tuition enabled does not require payment. Turning fee checks off does not alter invoices or payment balances. Closing registration takes precedence over fee clearance. Existing registrations remain viewable and downloadable, and admin-managed registration continues to follow its existing session clearance rules. Student registration checklists, dashboard clearance and the tuition page reflect these controls.

## Roles and audit

- Admin: draft/edit schedules including published schedules, delete schedules, publish fees, enable clearance, generate invoices, record adjustments, grant/revoke exemptions, inspect and verify payments.
- Bursar: draft/edit unpublished schedules, generate missing invoices, inspect invoices/payments and recheck Paystack.
- Accountant: inspect invoices/payments, download receipts and recheck Paystack; no financial approvals.
- Students: only their own invoices, payments and receipts.

Financial approvals and schedule changes are written to the existing activity log. Temporary exemptions require a reason, expiry date and semester; revocation requires a reason. They never erase the tuition balance.

Scholarships and waivers cannot exceed adjusted tuition. **Completed refund** requires the numeric Paystack refund ID and exact amount of a processed refund on this invoice. The server verifies these details; it does not transfer money. Repeated submissions and webhooks count the refund once. Overpayments remain visible as credit balances for bursary resolution.

Refund and dispute webhooks independently fetch the current gateway record, validate transaction ownership, currency and environment, and preserve an audit trail. Pending/processing/failed refunds do not reduce paid tuition. Processed refunds and accepted dispute amounts reduce net paid tuition and recalculate clearance. A refund linked to a dispute is not deducted twice. Unresolved or unrecognized dispute resolutions hold registration clearance without inventing an additional debt. Resolved declined disputes release that hold; ordinary balance requirements still apply. Explicit approved exemptions remain valid and existing course registrations are retained.

A reversed gateway transaction can be partially refunded: only verified refund/dispute amounts are deducted. A reversal without a verified amount holds clearance and checkout for bursary review. Original successful transactions and receipts remain historical evidence, with current financial notices on the receipt and invoice.

Historical manual refunds with an exact numeric Paystack ID are matched automatically. If old free-text references coexist with gateway deductions, balances are marked provisional and checkout pauses. An admin selects **Match an existing historical refund**, enters its Paystack ID and exact amount, and records a reason. This links the records without erasing history or deducting twice. Do not guess matches from similar amounts.

## Payment reliability

An invoice has many tuition charges; each charge has its own unique payment record/reference. Concurrent submissions resume the pending charge. A new attempt can replace a server-verified failed, abandoned or reversed attempt. An API timeout is not a verified failure and never authorizes a replacement by itself. Late success on an old attempt is still accounted for and may create an explicit credit balance. Repeated callbacks/webhooks credit each transaction once. The server chooses payment amounts; client amounts are ignored.

The existing Paystack HMAC-signed webhook and server verification are reused. Configure a publicly reachable HTTPS webhook and the correct test/live secret key as described in `payments.md`.

`php artisan payments:reconcile` rotates through up to 100 pending/failed/abandoned/reversed transactions plus a separate batch of up to 100 successful tuition transactions, including old sessions. The second batch checks for missed refunds/disputes and paginates their gateway lists. Failed requests rotate by attempt time without being marked successfully verified. Financial verification failures appear as warnings in System Health. The command is scheduled every ten minutes; larger backlogs take multiple runs. Run Laravel's scheduler (`php artisan schedule:work` locally, or `schedule:run` every minute in production). This command never initiates charges or transfers. Hosted checkout and public webhook delivery still require a real gateway test before accepting money.

Apply `2026_10_04_100000_add_payment_recovery.php` before running this version. It adds financial synchronization records and recovery timestamps without rewriting existing payments. Deploy the code and migration together, then run `payments:reconcile` and inspect System Health. The implementation follows [Paystack refunds](https://paystack.com/docs/payments/refunds/) and [disputes](https://paystack.com/docs/api/dispute/).

## Validation

Tests use an isolated SQLite database and mocked Paystack responses. Never point the test runner at the application database:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE=':memory:'
php artisan test tests/Feature/TuitionTest.php tests/Feature/PaymentTest.php tests/Feature/AdmissionTest.php tests/Feature/CourseRegistrationTest.php tests/Feature/AdminCourseRegistrationTest.php tests/Feature/AcademicSessionTest.php
```

The additive migration is `2026_10_03_100000_create_tuition_billing.php`. It retains existing payment records and the unique payment-per-request constraint; tuition uses separate charge records to support multiple payments on one invoice.
