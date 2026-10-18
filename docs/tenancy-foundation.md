# Shared-database tenancy foundation

Update: platform plan management, public institution signup, and first-semester subscription invoices/payment verification are now implemented. See [platform signup and billing](platform-signup-and-billing.md). Statements below about absent subscription invoices describe the original foundation stage.

Update: routing, authentication, academic/admin data isolation, files, caches and settings are now implemented. See [portal testing and isolation operations](tenant-isolation.md). The historical HTTP limitations below are superseded by that implementation.

## Confirmed decisions and implementation boundary

Each institution is a tenant. All institutions share one database; school records carry tenant_id. Central platform records (institutions, domains, plans and platform administrators) live in that same shared database. School accounts are institution-owned and platform administrator accounts are separate. Subdomain-first access remains the proposed routing approach.

Confirmed billing rules: billable students are enrolled students counted at semester start; promotional pricing applies to the first semester only, with the standard rate thereafter. Remaining billing decisions are listed below. No subscription invoices or charges are created by this foundation.

The new development database is educore_shared. The current live institution still uses its existing DB_DATABASE, schema, files and routes. This separate development database protects the live school until cutover; it is not a database per tenant. Provisioning creates institution-owned records in the shared database and never creates a database for each school.

Named central and tenant connections point to the same shared database during a TenantContext callback. Central models use their pinned connection; the context tracks institution identity and restores state/connections in finally. User, Team, RegistrationSetting and PromotionPolicy have ownership scopes and write guards. Other institutional tables have tenant_id scaffolding, but their models, raw queries, business-key constraints and authorization still require the Step 5 audit. HTTP, sessions, APIs, files, queues, cache and feature permissions are not yet integrated. Do not expose this foundation as a public multi-tenant portal.

The implementation uses Laravel's existing connection, migration and Eloquent APIs; no additional tenancy dependency is installed. Existing Jetstream teams remain internal school teams. Global numeric record IDs are unique across the shared database; different institutions may use the same email and matric number.

## Schema and lifecycle

- database/central-migrations: platform records, catalogue, domains, provisioning audit, and shared-schema ownership marker.
- database/migrations: existing institutional schema, unchanged for the legacy application.
- database/tenant-migrations: shared-schema tenant_id columns, tenant-scoped email/matric indexes, institution profiles and policy ownership.
- Institution profile, registration settings and promotion policy each have one record per tenant.
- Institutional tenant_id columns are non-null foreign keys in the new shared schema; infrastructure tables are deliberately left for the authentication/queue/cache phases.
- Installation rejects the current institution database and unrelated targets. Existing school data requires supervised migration, not automatic adoption.
- Provisioning statuses: pending, provisioning, failed, ready. ready means setup completed, not paid or publicly active.
- Seeded data: institution profile, policy defaults, initial administrator and personal Jetstream team. No example courses, faculties, students or production data are seeded.

Shared-schema migrations run once globally through tenancy:install. Provisioning a school runs no DDL. Application-wide identity/finance indexes and composite cross-table tenant constraints beyond the foundation remain Step 5 work.

## Three-tier catalogue and module audit

| Plan | Standard NGN / student / semester | Promotional NGN / student / semester | Promotion |
| --- | ---: | ---: | --- |
| Silver | 2,000 | 1,000 | First semester only |
| Gold | 4,500 | 2,250 | First semester only |
| Platinum | 6,500 | 3,250 | First semester only |

Rates are stored as integer kobo. Plan inheritance is stored centrally: Silver has 9 features, Gold 13 and Platinum all 18. Reinitialization preserves administrative price edits. Catalogue inclusion is not feature authorization; enforcement is Step 7. Public signup is disabled in the catalogue until the remaining billing and feature work is ready.

| Tier introduced | Feature | Current repository status |
| --- | --- | --- |
| Silver | Portal Control System | Implemented: dashboards and role middleware |
| Silver | Classroom Management | Partial: courses/class scheduling; distinguish from dynamic timetable |
| Silver | Student Record Management | Implemented: student accounts, admissions, academic history and maintenance |
| Silver | School Information Management | Partial: faculties/departments; institution branding/settings pending |
| Silver | Course Management | Implemented: courses, prerequisites and registrations |
| Silver | Payment Management | Implemented: Paystack payments and tuition workflows |
| Silver | User Management and Control | Implemented: accounts, roles and invitations |
| Silver | Application Management and Configuration | Partial: academic settings; tenant configuration pending |
| Silver | Mobile Application | Dedicated mobile client not found in this repository |
| Gold | Staff Management | Partial: StaffManagementController CRUD/course assignment; full intended scope needs definition |
| Gold | Assignment Management | Dedicated submission/grading module not found |
| Gold | Wallet | Wallet ledger/transactions module not found |
| Gold | Result Management | Implemented: results, grading, appeals, corrections and transcripts |
| Platinum | E-Learning | Partial: course materials; full learning platform not established |
| Platinum | Mailing | Partial: transactional emails; campaign/bulk mailing module not found |
| Platinum | Computer-Based Testing | Implemented: tests, questions, attempts and responses |
| Platinum | Dynamic Timetable | Partial: scheduling/conflict services; dynamic requirements need review |
| Platinum | Attendance Management | Implemented: sessions, records and scan flows |

Implemented means a corresponding module exists, not that its complete multi-tenant commercial behavior has been verified. Define essential account/security/payment notifications separately from Platinum Mailing, and basic staff accounts separately from Gold Staff Management. Product branding remains unresolved: the repository uses both EduCore and Euvion.

## Billing rules still pending

1. Exact enrollment source, census timestamp/timezone and exceptional exclusions.
2. How a new institution pays before importing students: estimated count, prepaid capacity or subsequent verified invoice.
3. Mid-semester additions, withdrawals, refunds and minimum charges.
4. Upgrade charges/proration, downgrade timing and retained access to historical records.
5. Semester boundaries, grace periods, expiry/suspension access and taxes.
6. Eligibility edge cases for the first promotional semester, standard renewal policy, and existing-school treatment.

The agreed student basis/census and one-semester promotion are stored in config/tenancy.php and the plan catalogue. Immutable count/rate snapshots and promotion consumption tracking will be implemented with subscriptions in Step 8. Do not activate paid access based only on plan_code or ready provisioning status.

## Backup and baseline

With MySQL running, configure client executables and run:

```powershell
$env:DB_BACKUP_BINARY = 'C:\xampp\mysql\bin\mysqldump.exe'
$env:DB_RESTORE_BINARY = 'C:\xampp\mysql\bin\mysql.exe'
php artisan development:baseline --verify-restore --no-interaction
```

Bundles are private under storage/app/backups/development-baselines. Each completed bundle contains a verified SQL dump, application-files.zip, and a manifest with SHA-256 hashes. Backup directories and symlinks are excluded from the file archive; every archived file is read back and hash-checked. A bundle without manifest.json is incomplete.

The SQL rehearsal imports into a disposable database, applies existing migrations, checks required tables and an administrator, and removes only that temporary database. It never invokes live restore or maintenance mode. Database and files are captured sequentially: pause writers for a production-consistent cutover snapshot. Securely retain source, environment configuration and APP_KEY separately and keep an off-machine copy. Bundles contain private records and must not be published.

Baseline before the foundation: 470 tests passed, 2 skipped, 3,250 assertions. A real SQL restore rehearsal and checksum verification of 18 storage files completed on 2026-10-13; its manifest records the exact bundle path and hashes. MySQL startup also exposed a damaged system permissions table from the earlier recovery: it was preserved, rebuilt from the saved export, and its 3 grant rows restored. CHECK TABLE succeeded.

## Installation and provisioning

Keep DB_DATABASE pointing to the existing school. CENTRAL_DB_DATABASE defaults to educore_shared; CENTRAL_DB_HOST/PORT/USERNAME/PASSWORD inherit DB_* for local development. All tenants share this target. Use appropriate runtime credentials and a separate migration operator before deployment.

```powershell
php artisan tenancy:install --create-database --no-interaction
```

Optionally create the first platform administrator interactively:

```powershell
php artisan tenancy:install --admin-email=owner@example.com --admin-name="Platform Owner"
```

The password is privately prompted. Automation can use PLATFORM_ADMIN_PASSWORD, or --password-env naming another variable. Initialization preserves existing platform passwords; platform login UI/guards follow in Step 4.

Provision a school interactively:

```powershell
php artisan tenancy:provision sample-school `
  --name="Sample School" `
  --domain=sample-school.educore.test `
  --owner-name="School Administrator" `
  --owner-email=admin@example.com `
  --plan=silver

php artisan tenancy:status
```

The initial school password must be at least 12 characters. Noninteractive automation reads TENANT_ADMIN_PASSWORD (or --password-env). Never place passwords in command arguments or source. Domains are reserved but unverified; public tenant routes are not enabled.

Provisioning acquires a per-institution MySQL advisory lock, writes school records in a transaction, creates a private storage directory and records success/failure stages. Retry with the same institution details. Existing passwords and records are preserved. ready retries are no-ops. No database or customer data is automatically dropped on failure. Suspended or incomplete institutions cannot enter normal explicit contexts.

foundation-alpha and foundation-beta are local development fixtures in educore_shared, using the same administrator email to verify tenant-scoped uniqueness. Generated passwords are not retained because these fixtures are not customer onboarding. Earlier separate-database development fixtures were superseded by the user's shared-database decision and are retained untouched; they are not referenced by the final implementation.

## Verification and next step

```powershell
php artisan test --compact tests/Feature/TenantFoundationTest.php tests/Feature/DatabaseBackupsTest.php
```

Checks cover tier inheritance/rates, confirmed promotion/census rules, same-email school accounts in one database, scoped direct-ID access, separate policy/team records, retry/password preservation, failed setup recovery, context cleanup, stale suspended records, missing-context refusal, ownership write guards, unrelated schema refusal and read-only backup rehearsal.

Final verification on 2026-10-13: 481 tests passed, 2 skipped, 3,327 assertions. Focused provisioning/backup checks: 31 passed, 1 skipped. Formatting and git diff whitespace checks passed. Real MariaDB verification confirmed two institutions in educore_shared using the same administrator email, isolated account/settings access, and context cleanup. The shared schema has tenant_id on 53 institution-owned tables. Database integrity checks passed for educore, mysql and educore_shared.

The temporary MySQL console process used for verification is stopped cleanly after checks. Start MySQL through XAMPP before running the operational commands. The local verified baseline is storage/app/backups/development-baselines/20261013-111551-zzHuYmSL/manifest.json.

Next: Step 4, institution domain resolution and authentication. Complete Step 5's full query/constraint audit and later infrastructure/billing/feature work before exposing public signup. Whole shared-database backups/restores must be platform-only; school-admin restore will require tenant-filtered data operations rather than dropping shared tables.
