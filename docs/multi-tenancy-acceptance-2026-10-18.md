# Multi-tenancy steps 1–9 acceptance report

Tested 18 October 2026 (America/New_York). No application fixes were needed during this run. Added a reusable, isolated MySQL acceptance script at tests/Manual/TenancyAcceptance.php.

## Results

| Verification | Result |
| --- | --- |
| Full automated suite | 513 passed, 2 skipped; 3,566 assertions; zero failures/errors |
| Tenant foundation and steps 7–9 cases within that suite | 41 passed; 312 assertions |
| Disposable MySQL acceptance rehearsal | 49 checks passed |
| Real local HTTP checks | 11 passed |
| Fresh baseline and restore rehearsal | Passed; SQL restored/upgraded in a disposable database; 22 storage files verified |
| Local shared schema | Semester and override schema present; both existing institutions ready |
| Fixture cleanup | No QA databases remain; temporary QA storage removed |

## Step-by-step coverage

| Step | What was checked | Outcome |
| --- | --- | --- |
| 1. Architecture and rules | One shared database, institution ownership, separate platform accounts, inherited package/rate catalogue, declared census, first-semester promotion and standard renewals | Tested rules passed; final product-branding choice remains a decision |
| 2. Safe baseline | Existing app regression suite, new checksummed SQL/storage baseline, actual restore and schema-upgrade rehearsal in a disposable database | Passed |
| 3. Central foundation | Fresh MySQL migrations, three institution provisions, administrator/team ownership, retry safety and password preservation | Passed locally; production runtime/migration credential separation remains a deployment task |
| 4. Routing and authentication | Two existing domain logins, central/school route separation, unknown-domain rejection, CSRF rejection, scoped browser sessions/reset tokens/API tokens and same-email accounts | Passed; MySQL rehearsal also rejected one school's token on another school |
| 5. Institutional data | Eloquent scopes, raw queries/OR conditions/joins, cross-school foreign keys and direct IDs, bulk writes and account isolation | Passed |
| 6. Files, cache and branding | Identical filenames/cache keys across schools, scope cleanup, branded ID-card template, rendered account-email name and institution login URL, protected backup endpoints | Passed; this run checks rendered templates rather than visual PDF layout |
| 7. Plan permissions | Silver/Gold/Platinum feature boundaries, direct request and mutation denials, menus, override reasons/expiry and unpaid/suspended restrictions | Passed |
| 8. Semester billing | Integer-kobo rates, fixed invoice snapshots, identical/altered retries, overlapping periods, standard-rate renewals, future plan activation, expiry and legacy open-ended fallback prevention | Passed |
| 9. Signup and onboarding | Signup for all three tiers, persisted billing accounts, provisioning recovery, checkout reuse, activation, verification mismatches, signed/duplicate webhook handling and invoice dashboards | Passed with simulated gateway responses; real provider checkout remains untested |

## Remaining validation limits

- PLATFORM_PAYSTACK_SECRET_KEY is unconfigured locally. A real Paystack test-mode checkout/callback still needs a configured test secret. No real gateway transactions or charges were made.
- Final EduCore/Euvion branding is not settled by a test run.
- Production database privileges, HTTPS/DNS deployment, operator permissions and cutover are outside this local acceptance run.
- The Windows symlink protection test was skipped because symlink creation is not permitted here. The disabled-registration case was skipped because registration is enabled.
- The local HTTP checks exercise actual server responses using curl, including host routing and CSRF. This is not browser visual/accessibility testing.

## Reproduce and evidence

Full regression:

    php vendor/pestphp/pest/bin/pest --compact --log-junit storage/logs/steps-1-9-2026-10-18.junit.xml

Isolated MySQL rehearsal (local/testing only; creates a uniquely named disposable database, blocks outbound gateway requests, and removes only its exact database/storage afterward):

    php tests/Manual/TenancyAcceptance.php

Fresh SQL/storage baseline and disposable restore:

    php artisan development:baseline --verify-restore

Run artifacts:

- storage/logs/steps-1-9-2026-10-18.log
- storage/logs/steps-1-9-2026-10-18.junit.xml
- storage/logs/steps-1-9-mysql-2026-10-18.log
- storage/logs/steps-1-9-http-2026-10-18.json
- storage/logs/steps-1-9-environment-2026-10-18.json
- storage/logs/steps-1-9-baseline-2026-10-18.log
- storage/app/backups/development-baselines/20261018-065118-2bvbqqOt/manifest.json

The existing shared institutions and legacy school records were not replaced or reset. The HTTP server is available at http://educore.test:8000/pricing, with existing host mappings for educore.test and both foundation institution domains.
