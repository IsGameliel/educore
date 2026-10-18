# Platform plans, institution signup, and first-semester payments

Update (17 October 2026): feature permissions, explicit semester periods, renewals, invoice history and audited feature overrides are implemented. See [steps 7–9 policy and operations](plans-semester-billing-and-onboarding.md). Historical statements below about pending renewals and module permissions describe the previous stage.

Open the platform dashboard at `http://educore.test:8000/dashboard` and sign in with your platform administrator account. Use **Create plan** or **Edit** to change a plan's name, naira rates, included features, and signup availability. Plan codes remain fixed after creation. Existing invoices retain their plan name, feature list, rates, and enrollment count when a plan changes. Re-running `tenancy:install` preserves these edits.

The dashboard shows registered institutions, new registrations in the past seven days, unpaid subscriptions, provisioning failures, and confirmed subscription totals. Filter institutions by name, provisioning status, or subscription status. Open an institution to see its contact details, portal address, current student/staff account counts, semester invoices, payment attempts, and provisioning history. Suspend or resume portal access there; resuming access does not bypass an unpaid subscription.

## Signup flow

1. Open `http://127.0.0.1:8000/pricing` or `http://educore.test:8000/pricing`.
2. Choose Silver, Gold, or Platinum. The signup link opens on the central domain.
3. Enter the institution name, unique account ID and institution-owned portal hostname (for example, portal.rivers-college.gov.ng), owner details, a password of at least twelve characters, semester name/start date, and the count of students enrolled at semester start.
4. Confirm the enrollment declaration. The application saves an institution billing account, provisions its separate school administrator, and creates a fixed first-semester invoice.
5. Review the total and choose **Proceed to secure payment**. If checkout is interrupted, return through `http://educore.test:8000/subscribe/login` using the school slug, owner email, and original password.
6. Server-side payment verification opens branding setup immediately. Dashboard entry also requires operator verification of the school-owned address, DNS and HTTPS. Use **Check payment status** if the gateway redirect was interrupted.

For local testing, add each new school's address to the Windows hosts file. For example, for a requested hostname `portal.rivers-college.gov.ng`, add `127.0.0.1 portal.rivers-college.gov.ng` on your development computer only. Keep MySQL and `php artisan serve --host=127.0.0.1 --port=8000` running. Production requires school-controlled DNS and HTTPS for each institution-owned hostname. See [domain routing](domain-routing.md) for operator approval; signup no longer creates an Euvion subdomain.

If school setup is interrupted, the saved institution account exposes **Retry school setup**, which requires the account password. Checkout is blocked until setup completes. Passwords are only retained as hashes. An operator can reset the school owner and billing account passwords with `php artisan tenancy:admin-password rivers-college`; this also revokes the owner's sessions and tokens.

## Agreed rates

| Plan | Standard per student per semester | First-semester promotion |
| --- | ---: | ---: |
| Silver | ₦2,000 | ₦1,000 |
| Gold | ₦4,500 | ₦2,250 |
| Platinum | ₦6,500 | ₦3,250 |

The first-semester invoice uses the institution's declared enrolled count at semester start. For example, 100 Silver students cost ₦100,000 for the first semester. Later-semester renewal, adjustments, upgrades, and expiry remain pending commercial decisions; this change does not invent or automate those rules. Features absent from the repository are labelled **Coming soon**; incomplete modules are labelled **Available in part**. This catalogue controls displayed package contents; module-level feature permissions are a subsequent development phase.

## Paystack configuration

Configure these variables locally in `.env`:

```dotenv
PLATFORM_URL=http://educore.test:8000
INSTITUTION_BASE_DOMAIN=educore.test
PLATFORM_PAYSTACK_SECRET_KEY=your_paystack_test_secret_key
```

Use a real `sk_test_...` key from your Paystack dashboard for development, then run `php artisan config:clear`. Never put a secret key in a Blade template or commit it. Platform subscriptions use this dedicated key; existing school/student payments continue using `PAYSTACK_SECRET_KEY`.

The callback is `/subscribe/callback`. Configure the platform Paystack webhook as `https://YOUR-CENTRAL-DOMAIN/subscribe/webhook` when the application has a publicly reachable HTTPS address. Local-only domains cannot receive external webhooks; the callback and **Check payment status** support local verification instead.

Checkout calculates the amount on the server in kobo. Verification checks gateway success, reference, exact amount, NGN currency, owner email, test/live mode, and gateway transaction identifier. Webhooks require the Paystack HMAC signature and are verified with the provider before activation. Duplicate confirmations do not create duplicate invoices or subscription credits. No browser query parameter can mark an invoice paid. Existing manually provisioned test schools retain their explicitly labelled `not_required` subscription state.

## Validation

Focused tests:

```powershell
php artisan test --compact tests/Feature/TenantFoundationTest.php tests/Feature/AppHealthTest.php tests/Feature/DatabaseBackupsTest.php
```

Coverage includes plan creation/editing, immutable invoice snapshots, enrollment validation, signup recovery, real session persistence, authentication separation, portal suspension, checkout reuse, invalid/duplicate webhooks, and amount/currency/email/test-mode verification. Gateway responses in automated tests are simulated; they do not charge customers.

Full regression run: **502 passed, 2 skipped, 3,459 assertions**. Real local HTTP checks also passed for signup with CSRF/cookies, owner account session persistence, the missing-payment-key error redirect, platform administrator login, and institution monitoring. The temporary HTTP fixtures were removed afterward.

A verified shared database backup was taken before the migration at `storage/app/backups/before-platform-subscriptions/manual/educore_shared_2026-10-13_145536_lQHXq7tS.sql`. The new migration was applied to the shared development database; the legacy school database was not migrated.
