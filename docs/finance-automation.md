# Finance automation

## Reuse fee schedules

Open **Finance > Semester Fee Templates**. Save an existing First, Second or Annual schedule as a named template. Choose the target academic session, departments, levels, student category and fresh deadlines to create drafts. Existing schedules for the same cohort and period are skipped. Templates preserve the source fee breakdown; deleting a template leaves schedules and invoices intact.

Review each draft in **Tuition Fee Schedules**, then publish as an admin. Drafts do not bill students. Publishing bills matching students in the active session. Existing invoices keep their original amounts and deadlines.

## Automatic invoice generation

Invoices are generated after student creation or changes to department, level, entry year or student role, including imports. Publication, approved session activation and student billing/registration checks also generate missing invoices. The scheduler runs `tuition:generate` every 15 minutes to catch bulk enrollment changes. Incomplete enrollment must be corrected before billing. Repeated generation preserves existing invoices.

## Dashboard and email reminders

Students see reminders on their dashboard and tuition pages. Email reminders run daily at 08:00 in the application timezone: once during the seven days before a deadline, once on the due date, then once per overdue week. If automation starts late, the next run catches the current reminder window. Annual installment reminders use only the unpaid installment requirement, then the remaining balance deadline.

Paid, cancelled and withdrawn invoices are excluded. Queued jobs recheck the balance and invoice status before sending. **Finance > Payment Reminders** shows queued, sent, skipped and failed records. Sent means accepted by the configured mail transport, not confirmed inbox delivery. Ordinary repeated scheduler runs do not duplicate the same reminder window.

Configure working mail credentials and an accessible `APP_URL` before production delivery. `TUITION_EMAIL_REMINDERS=false` disables email while dashboard reminders remain available. Run both Laravel's scheduler and queue worker continuously, using a service/process manager in production. During local development use separate terminals:

```console
php artisan schedule:work
php artisan queue:work --tries=3 --timeout=90
```

`php artisan tuition:remind --dry-run` reports currently eligible invoice balances without creating reminders or sending email; it does not subtract windows already sent. Check **System Health** for scheduler, worker, invoice generation and reminder health. A failed mail job retries three times; the next daily dispatch can retry failed/stale records if their reminder window is still current.

## Review withdrawn invoices

Deleting a schedule stops checkout and reminders on its invoices while keeping financial history. Open **Finance > Withdrawn invoices**, then an invoice. Only admins can approve a decision, with a reason:

- **Resume:** restore collection on the same invoice with its original amounts, payments and deadlines.
- **Retain:** acknowledge an invoice with no outstanding balance as historical. A later recorded refund that reopens the balance brings it back for review.
- **Cancel:** only available when no payment attempt or adjustment exists. The cancelled record remains, and automatic billing for that overlapping period is held.
- **Replace:** cancel an untouched invoice and explicitly issue from a published replacement schedule for the same session, department, level, category and period. This also releases a previously cancelled invoice's billing hold. Both records remain linked.

Uninitialized unpaid checkouts can be retired during cancellation/replacement. Every initialized checkout requires fresh Paystack verification as failed or abandoned. Pending, successful, reversed or unverifiable checkouts block the action. Initialization intent is persisted before network calls, so a lost response cannot make a checkout look untouched. Old attempts and references remain in history and further local checkout is disabled. Continue monitoring retired references: a late gateway success stays on its original invoice and must be reviewed by the bursary; it is never silently transferred. Adjustments or paid charges prevent replacement. Cancellation alone does not grant registration clearance.

## Financial approvals

During migration, older payment rows are conservatively marked as potentially initialized because past initialization timeouts cannot be distinguished from untouched checkouts. These require gateway verification before replacement. Late payments on cancelled invoices appear as a warning under **Approvals & settlements**.

Switching between installment and full checkout retires an unpaid attempt only if initialization has never begun. Initialized pending checkouts retain their existing link. Previously failed or abandoned attempts are freshly verified again before issuing another reference.

Admins and bursars request scholarships and waivers from invoice details. Requests do not change balances. A different administrator approves or rejects them under **Finance > Approvals & settlements**. Approval locks the invoice and rechecks the current charge, preventing simultaneous approvals from over-crediting it. Repeated decisions are rejected. The requester, reviewer, reasons and decision remain in the audit history. Existing recorded adjustments are preserved. Verified gateway refunds remain financial facts and do not require approval to reflect actual returned funds. Temporary registration exemptions retain their existing admin approval workflow.

Queued email notifications alert other administrators to requests and notify the requester and student of decisions. Run a queue worker with working mail credentials; the approval page is the authoritative decision record if email fails.

## Settlements and reports

`php artisan payments:settlements` imports the last 30 days of Paystack settlements daily at 06:00 through the scheduler. Use `--from=YYYY-MM-DD --to=YYYY-MM-DD` for older history. It paginates settlements and their transactions, checks the key environment and currency, and matches local successful payments by reference, transaction ID and gross amount. Repeated imports update the same settlement. Gross, gateway fees, net payout, matched amount and unknown/mismatched transactions appear on **Approvals & settlements**. Differences in transaction totals and net payouts are flagged for deduction/split review. Imported gateway settlements do not establish bank receipt: compare them with bank statements. Settlement imports never change tuition balances or initiate payouts. Failures appear under System Health.

Invoice reports process financial calculations in bounded batches and retain only the requested page. Payment report totals use a single aggregate query. Finance staff can download a streaming payment CSV from the finance navigation or `/finance/payments/export`, with the same purpose/status/search/date filters as the payment list. Amount columns are integer kobo; net is the recorded successful amount less gateway deductions. Text cells are protected against spreadsheet formula injection.
