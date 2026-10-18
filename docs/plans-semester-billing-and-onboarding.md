# Plans, semester billing, and onboarding (steps 7–9)

Implemented on 17 October 2026. This extends the existing shared-database signup and Paystack checkout.

## Commercial policy: semester-fixed-v1

- Currency is NGN; all arithmetic uses integer kobo. Minimum declared enrollment is one student, maximum 100,000. The invoice is one line: declared enrollment multiplied by the snapshotted unit rate. No additional tax is calculated under this policy.
- The owner declares enrolled students at semester start, including before student imports. This is an attested census, not an automatically verified headcount. Current account counts remain separate from billing.
- New signup requires an explicit semester start and end. A semester may span at most 366 days. Dates are inclusive in the institution timezone (currently Africa/Lagos at signup; administrators can change the timezone).
- Only the first issued semester receives the promotional rate. Every subsequent semester uses the selected plan's current standard rate when its invoice is created.
- New students, withdrawals, upgrades and downgrades take effect on the next semester invoice. Issued enrollment, dates, rates, package contents, totals and price version cannot be edited through the application. There is no mid-semester proration, automatic debit, automated credit or refund. Support handles payment corrections separately; this release has no gateway refund action.
- Next semesters must begin after the preceding semester's end. Only one invoice can exist per institution/start date. Identical retries return the existing invoice; altered retries and overlapping periods are rejected. Complete outstanding payment before another invoice is issued.
- Owner-confirmed update, 18 October 2026: verified payment grants portal access immediately, including before the first paid semester's start date. An existing current paid period keeps its original feature permissions; otherwise the next paid package grants immediate access. Access still expires after the paid end date. Billing login, invoice history and renewal remain available centrally. School records/files are retained; this does not alter issued dates, enrollment, amounts or feature snapshots.
- Existing pre-upgrade invoices with no end date retain open-ended access until a later invoiced semester starts; the migration deliberately does not invent expiry dates for existing purchases. Their first renewal must start after the original start date. Once the next semester starts, its payment and dates control access; an expired or unpaid renewal never falls back to an old open-ended purchase. Operators should agree legacy period dates before production rollout.
- Manually provisioned institutions explicitly marked not_required retain their payment exemption and use their assigned catalogue plan. Feature restrictions still apply.

## Feature permissions

FeaturePermissions is the common permission service. Paid invoices supply the current feature snapshot; later catalogue edits apply to new invoices. Unsupported modules labelled Coming soon remain unavailable.

Existing result workflows/transcripts are Gold; CBT, attendance and course-material delivery are Platinum. Basic staff account administration remains Silver user management. Fixed class scheduling and its safety conflict checks remain Silver classroom management. Advanced staff management, automatic dynamic timetable generation, mobile applications, assignments, wallets, e-learning beyond materials and bulk mailing are not implemented by this change. Transactional security/account/payment emails remain available across plans.

Web/API route middleware checks actual controller actions as well as named routes. Premium database writes/deletes and result workflow/transcript service entry points also check permissions, covering jobs and bulk operations. Direct tenant asset URLs for course materials check the learning permission. Current Livewire components only edit profiles and do not expose premium modules. Admin, student and lecturer menus hide unavailable modules; administrators have a Plans & Renewal link. Permission checks supplement existing role and tenant ownership checks.

Platform administrators can grant/deny a feature temporarily, recording a reason, administrator, expiry (maximum 90 days) and audit event. Expired overrides stop applying; a newer unexpired override takes precedence. They cannot bypass payment, institution suspension or unavailable modules.

## Billing and onboarding

Signup and renewal use SemesterBilling to issue versioned immutable invoices. price_version hashes the plan code, both rates and feature snapshot. This preserves independently identifiable commercial versions without rewriting earlier invoices. subscription_events records invoice issuance, first successful invoice payment and feature overrides. Invoice history retains prior prices and packages.

The billing account chooses next semester dates, enrollment and plan; payment uses the existing independent platform Paystack key. Payment verification checks reference, exact amount, NGN, customer email, test/live mode and transaction ID. Duplicate webhook delivery has one activation/audit effect. Verified failed/abandoned attempts permit a fresh checkout; unconfirmed pending checkout links are reused. New checkout is refused after the invoiced semester ends. Late gateway confirmations remain recorded against their original invoice and cannot extend its dates.

School suspension continues to override paid subscription access. Paying ahead does not switch the active package. The effective package is selected by paid semester dates, without requiring a scheduler.

## Local rollout and verification

Back up the shared development database before running php artisan tenancy:install. The new central migration adds invoice end dates, version/policy identifiers, feature override history and subscription events. It does not migrate the legacy institution. Never run a whole-school tenant restore against the shared database.

Run php artisan test --compact tests/Feature/TenantFoundationTest.php, then the full regression suite. Gateway tests simulate responses; no real charges are made. Production deployment, DNS/HTTPS, per-institution tuition gateways, scheduled operational jobs and tenant restore/export remain steps 10–14.

## Completed validation and development rollout

The full application regression run passed: **509 passed, 2 skipped, 3,534 assertions**. After the final access-boundary refinements, seven focused checks passed (66 assertions); an additional legacy-period check passed and each of Silver, Gold and Platinum completed simulated checkout/verification and rendered its plan-aware dashboard (three tests, 27 assertions). Final subscription-state/platform-view checks also passed. Updated Blade views compile, subscription routes are registered, and git diff whitespace checks pass.

Read-only HTTP-kernel checks using the actual shared MySQL database returned 200 for pricing, institution signup, billing login, platform login and institution login. These checks do not make real gateway charges.

The shared development migration was applied after backup verification. Institution, user, subscription invoice and payment record counts were preserved. The original school's database was not migrated. Backup: storage/app/backups/before-semester-entitlements/manual/educore_shared_2026-10-18_022341_yjb1TFxb.sql (server timestamp; rollout was 17 October in America/New_York). SHA256: e04a204062588de295cd514e5b339c13b712b30dd96649f9c18fa5cba937c593.
