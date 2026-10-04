# Paystack payments

Server-controlled fees: application ₦10,000, official transcript request ₦30,000, result appeal ₦15,000. Amounts are stored as integer kobo in `App\Models\Payment`.

Tuition uses configurable session invoices rather than these fixed request fees. See [Tuition setup and operations](tuition.md) for schedules, installments, clearance, receipts and adjustments.

## Setup

1. Add `PAYSTACK_SECRET_KEY=sk_test_...` to `.env`. Do not commit the key. Hosted checkout only needs a server-side secret key.
2. Set `APP_URL` to the actual site URL. Run `php artisan migrate` and `php artisan config:clear` (or rebuild the configuration cache in production).
3. Configure Paystack's webhook URL as `https://YOUR-DOMAIN/payments/paystack/webhook`. The app supplies `/payments/paystack/callback` when creating checkout.
4. Test all three workflows with test credentials. Webhooks require a public HTTPS URL; Paystack cannot reach localhost. Browser callbacks can work locally.
5. Before launch, configure the approved merchant account's live secret key and live webhook URL.

## Workflow

Forms are saved as `awaiting_payment`. Only a verified successful payment activates the applicant's student account or submits an academic request for review. Existing completed admissions and pre-integration requests remain unchanged. Staff-created transcript requests require the student to pay through **My Payments**. Student result copies remain unchanged.

Students can resume checkout and check status from **My Payments** or the saved request. A payment reference uses one checkout link. Callback and webhook fulfillment is transactional and idempotent. Failed initialization preserves the form. If initialization times out after Paystack accepted it but before the checkout URL was received, check that reference in Paystack before intervening; do not create another charge blindly.

Verification checks reference, amount, currency, customer email and test/live environment. Webhooks require HMAC-SHA512 signatures and independently verify the transaction. Processing failures return 503 for retries. Card credentials and authorization payloads are not stored.

Admins use **Payments** (`/admin/payments`) for filtered totals, payer/reference search, fee/status/date filters and verification of payments. There is no manual “mark paid” override. Refunds and disputes are initiated/resolved in Paystack. Tuition synchronizes their verified financial effects through signed webhooks, manual checks and scheduled reconciliation; see `tuition.md`. This does not initiate refunds or revoke completed non-tuition services. Payment history retains original transaction amounts; finance invoice totals show net tuition after financial changes.

## Tests

Use an isolated database, never the application database. In PowerShell:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE=':memory:'
php artisan test --filter='Payment|Admission|AcademicWorkflow|AdmittedStudent'
```

References: [Accept payments](https://paystack.com/docs/payments/accept-payments/), [Verify payments](https://paystack.com/docs/payments/verify-payments/), [Webhooks](https://paystack.com/docs/payments/webhooks/).

## Gateway validation and release checks

Run `php scripts/paystack-connectivity-check.php` for a read-only authenticated API check. It prints only connection/environment status and never exposes the secret. This does not charge a customer or prove hosted checkout/webhook delivery.

The automated payment, recovery, tuition and finance tests use documented Paystack-shaped responses, including full tuition initialization → callback verification → duplicate signed webhook → receipt → registration clearance. They cover all fee purposes, wrong amounts/currencies/customers/environments, failed initialization, outages, late success, refunds, disputes, approvals and settlement mismatches. Use an isolated database for these tests.

Before release, use the deployed app with a Paystack test key and a publicly reachable HTTPS webhook:

1. Submit an application, transcript request, appeal and tuition installment. Complete each through Paystack hosted test checkout and confirm the callback updates the saved request exactly once.
2. Close the browser after payment to validate webhook-only fulfillment. Redeliver the event and check for one receipt and one balance contribution.
3. Exercise failed/abandoned checkout, a timeout and subsequent status checks. Verify that pending/unknown transactions cannot be replaced and that a freshly verified terminal failure can be retired.
4. Pay the remaining tuition balance, download the receipt and confirm semester registration clearance. Complete a test refund/dispute in Paystack and check recovery and clearance changes.
5. Review a bursar adjustment with a different admin. Check the unchanged balance before approval, updated balance afterward, audit entries and queued emails.
6. Import settlements where the account exposes them, compare exceptions against gateway records and bank statements, and test mail/scheduler/queue health. Sandbox accounts may have no settlement payouts; this remains a production release check.

Settlement endpoints and field mappings follow the [Paystack Settlement API](https://paystack.com/docs/api/settlement/).
