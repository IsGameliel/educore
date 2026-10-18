# EduCore multi-tenancy and subscription development plan

Status: Steps 1–13 are implemented for the shared development platform, using a staged commercial catalogue. Step 14 has migration/import/activation tooling, an actual-data rehearsal and domain-specific deployment configuration; live cutover remains blocked by absent hosting and external production acceptance. Legacy issues were subsequently resolved through audited archival and account consolidation. Step 8 uses the fixed-semester policy documented in [plans, semester billing, and onboarding](plans-semester-billing-and-onboarding.md). Existing invoices retain their original terms. Configure the dedicated platform and school Paystack keys for real checkout. Production installation, missing commercial modules, and existing-school cutover remain separate. See the [operations runbook](multi-tenancy-operations.md).

Acceptance run on 18 October 2026 after steps 10–12: **524 passed, 2 skipped**, plus 59 disposable-MySQL checks and eight post-upgrade real HTTP checks. See [operations acceptance report](multi-tenancy-operations-acceptance-2026-10-18.md), and the earlier [steps 1–9 report](multi-tenancy-acceptance-2026-10-18.md) for the original baseline/restore rehearsal.

## Objective and architecture

Convert EduCore into one application serving multiple institutions. Each institution has isolated users, academic records, finance records, settings, and files, and selects Silver, Gold, or Platinum during signup. Subscription charges are per billable student per semester.

Confirmed architecture: one shared database contains central platform records and institutional records. Institution-owned tables use tenant_id, scoped queries, and tenant-aware constraints. Platform administrators are separate from school accounts. The development database is separate from the current live school until supervised cutover. Sessions, files, queues, and authorization also require isolation.

Keep institutional tuition payments separate from institution subscription payments. Existing Jetstream teams remain an internal collaboration feature unless the architecture review explicitly changes their purpose.

## Working process

- Complete one step at a time. Update its checklist and record validation before starting the next step.
- Keep changes reviewable and preserve existing single-institution workflows during development.
- Use local/test databases until the migration rehearsal and production cutover are explicitly scheduled.
- For each step, document schema changes, configuration, operational requirements, tests, and remaining decisions.
- Test isolation with two institutions sharing a database and using identical emails, matric numbers, and business codes. Test direct access to another institution's global record IDs.
- Do not advertise or enable a module until its implementation and plan restrictions have passed review.

## Agreed plan catalogue

| Plan | Standard rate | Promotional rate | Unit |
| --- | ---: | ---: | --- |
| Silver | NGN 2,000 | NGN 1,000 | Student / semester |
| Gold | NGN 4,500 | NGN 2,250 | Student / semester |
| Platinum | NGN 6,500 | NGN 3,250 | Student / semester |

Silver: Portal Control System; Classroom Management; Student Record Management System; School Information Management System; Course Management System; Payment Management System; User Management and Control; Application Management and Configuration; Mobile Application.

Gold: everything in Silver, plus Staff Management System; Assignment Management System; Wallet System; Result Management System.

Platinum: everything in Gold, plus E-Learning; Mailing System; Computer-Based Testing; Dynamic Timetable System; Attendance Management System.

The commercial mapping needs a module audit. Distinguish basic class management from dynamic timetable functionality, ordinary staff account administration from the Staff Management System, and essential account/payment notifications from the Platinum Mailing System. Determine whether the mobile application actually exists and what it includes. Confirm EduCore versus Euvion branding.

## Step 1 — Confirm architecture and commercial rules

- [x] Confirm one institution per tenant and shared-database architecture; retain proposed subdomain-first access.
- [x] Use institution-owned school accounts and separate platform administrators.
- [x] Inventory existing modules and map each to a stable feature key and tier; flag incomplete modules.
- [x] Confirm enrolled students counted at semester start. Define exact enrollment source and exceptional exclusions before billing implementation.
- [x] Use the owner-attested enrolled count at semester start, including before imports; do not present it as automatically verified enrollment.
- [x] Confirm promotional pricing for the first semester only. Eligibility edge cases and renewal policy remain to be specified.
- [x] Adopt semester-fixed-v1: minimum one enrolled student, count/plan changes next semester, no mid-semester proration or automatic debits, no grace period, and support-managed payment corrections without automated refunds.
- [x] Require inclusive start/end dates (maximum 366 days), institution timezone boundaries, integer NGN kobo, and no additional tax calculation under this policy.
- [x] Block school portal access outside a paid semester or during suspension; retain records and central billing/history access. Tenant-filtered exports remain step 12.
- [ ] Confirm product branding and tenant-domain conventions.

Deliverable: architecture decision record, feature matrix, and billing rules. Done when rules are explicit enough to write acceptance tests without inventing commercial behavior.

## Step 2 — Establish a safe baseline

- [x] Inventory the main models, raw queries, authentication, jobs, schedules, integrations and backups; carry the detailed per-query audit into Step 5.
- [x] Create and verify SQL/storage backups and rehearse SQL restoration into a disposable database.
- [x] Run the existing application test suite as the baseline.
- [x] Create two-institution fixtures and document environment setup.

Deliverable: migration inventory and working baseline checks. Done when the existing application can be restored and its critical workflows are verified.

## Step 3 — Build the central tenant foundation

- [x] Implement Laravel-native explicit tenant context and row scopes for provisioning; HTTP/Livewire/Sanctum integration follows in Step 4.
- [x] Add central institutions, domains, platform administrators, plans, and provisioning lifecycle records.
- [x] Keep platform records explicitly connected, separate migration paths, and prepare tenant_id columns in the shared schema.
- [x] Implement retryable provisioning of school settings, profile, first administrator, personal team, and storage directory; no per-institution database creation.
- [x] Record failure stages and block explicit contexts for institutions that are not ready. Public tenant routing remains disabled.
- [ ] Configure production runtime permissions separately from migration/installation credentials before deployment.

Deliverable: provision two test institutions through a command/service in one shared database. Done when initial accounts/settings are isolated, retries preserve records and credentials, and installation leaves the current institution unchanged.

## Step 4 — Resolve tenants and isolate authentication

- [x] Separate platform and institution routes; initialize tenancy before authentication, validation, and record binding.
- [x] Reject unknown domains and enforce tenant lifecycle states without falling back to another institution.
- [x] Adapt Fortify login/registration/reset/OTP/two-factor and Jetstream actions through the tenant connection/context.
- [x] Isolate sessions, remember-me lookup, throttles, reset tokens and Sanctum tokens.
- [x] Separate platform and school credentials/guards. No user-controlled tenant switching is exposed.
- [x] Preserve existing merged/deactivated-account restrictions.

Deliverable: independent logins on two tenant domains. Done when sessions, reset links, tokens, invitations, and direct record URLs cannot cross institutions.

## Step 5 — Isolate institutional data and workflows

- [x] Apply model scopes and a mandatory tenant predicate in the tenant SQL grammar, including raw queries and bulk operations.
- [x] Enforce ownership on writes, foreign links, pivots and polymorphic records; scoped reads cover imports/exports/reports.
- [x] Scope validation, business unique indexes and route binding; test aliases, joins, OR clauses and cross-school references.
- [x] Keep account merges and academic/finance operations scoped; platform tables are excluded from school merge scans.
- [x] Pin central operations and isolate infrastructure access. Queue processing restores context before model deserialization.

Deliverable: isolated academic and administrative workflows. Done when tenant A cannot read or mutate tenant B through UI, API, imports, exports, or bulk actions.

## Step 6 — Isolate files, cache, settings, and links

- [x] Scope private/public disks and exports by institution. Whole-database backups are denied to schools; tenant export/restore remains Step 12.
- [x] Scope download/delete/scan/verification record lookups and file roots; protect public-disk assets with school authentication.
- [x] Isolate caches, database flushes, locks and temporary file roots; Livewire shares domain/context handling.
- [x] Add administrator-managed branding, logo, contact details and timezone; academic setting models are tenant-scoped.
- [x] Apply institution URLs and branding to emails, receipts, PDFs, student IDs and queued work.

Deliverable: tenant-aware storage and presentation. Done when two institutions can use identical filenames/cache keys without collision or unauthorized access.

## Step 7 — Implement plans and feature permissions

- [x] Create central plan, price-version, promotion, and feature definitions; seed the agreed catalogue.
- [x] Implement subscription states and a single feature-permission service.
- [x] Enforce permissions across controllers, APIs, Livewire, services, and jobs.
- [x] Adapt navigation and show clear upgrade options while retaining existing records.
- [x] Keep essential security and transactional notices available according to Step 1 rules.
- [x] Add audited platform overrides with explicit duration and reason.

Deliverable: Silver, Gold, and Platinum permission checks. Done when direct requests cannot bypass restrictions and all feature keys match the reviewed module matrix.

## Step 8 — Implement semester subscription billing

Implemented with the semester-fixed-v1 policy: first-semester promotion, standard-rate renewals, declared census, explicit inclusive semester dates, immutable price/feature versions, next-semester package/count changes, no automatic debits or mid-semester adjustments, no grace period, and support-managed payment corrections. See the implementation notes for preserved legacy invoice terms and unavailable automated refunds.

- [x] Store semester periods, subscription state, immutable single-line invoice values, and payment attempts centrally.
- [x] Snapshot student count, unit rate, price version, promotion, semester, and applicable tax on invoices.
- [x] Store monetary values in integer minor units and use deterministic calculations.
- [x] Record the owner-attested enrollment declaration and apply changed counts on subsequent semester invoices; automated census verification is not part of this policy.
- [x] Apply standard-rate renewals, next-semester count/plan changes, no grace period, and the documented support-managed payment-correction policy. Automated credits/refunds are unavailable.
- [x] Prevent duplicate invoices and repeated charging; retain an audit trail.

Deliverable: reproducible semester invoices. Done when pricing examples, boundaries, retries, and adjustments pass billing tests and price edits cannot alter issued invoices.

## Step 9 — Implement signup, checkout, and onboarding

- [x] Build plan selection → institution details → semester/student-count confirmation → invoice review → payment → portal activation. School records are provisioned before checkout, with access blocked until verified payment.
- [x] Display standard and promotional rates, inclusions, billing unit, eligibility, and amount payable accurately.
- [x] Collect school details and first administrator information securely; support resumable signup.
- [x] Integrate platform subscription checkout separately from student tuition checkout.
- [x] Verify payments server-side and make callback/webhook handling idempotent.
- [x] Provision/activate only according to the approved payment policy; support pending, failed, and delayed payments.
- [x] Add institution and platform subscription dashboards.

Deliverable: completed signup for each tier. Done when duplicate callbacks cannot provision twice, declined payments cannot grant paid access, and delayed successful payments recover correctly.

## Step 10 — Adapt institution payments and integrations

- [x] Implement the agreed per-school Paystack account or platform settlement model.
- [x] Store credentials securely and generate tenant-correct payment callbacks.
- [x] Resolve webhook ownership safely, verify signatures, and isolate refund/dispute/reconciliation processing.
- [x] Review institutional mail and Firebase integrations for tenant ownership and configuration.
- [x] Ensure all integration settings are cleared when tenant context ends.

Deliverable: isolated institution integrations. Done when forged/duplicate webhooks and overlapping external identifiers cannot update another school's payments.

## Step 11 — Adapt queues, schedules, and operational controls

- [x] Attach tenant identity to every institutional job, including SendTuitionReminder and failure handlers.
- [x] Initialize tenant context before queries/model restoration and clear it in all exit paths.
- [x] Keep queue infrastructure on its designated central connection.
- [x] Run tuition generation, reminders, payment reconciliation, settlements, and renewal tasks per eligible institution.
- [x] Add per-tenant overlap locks, timezone handling, quotas, and suspension policies.
- [x] Record tenant context in logs, audit trails, and health monitoring.

Deliverable: tenant-safe background processing. Done when one worker can process alternating institutions, failures, and retries without leaking context or duplicating billing effects.

## Step 12 — Make backups, upgrades, and tenant lifecycle safe

- [x] Give school administrators only tenant-filtered exports/restores and their own files; reserve whole shared-database backups/restores for platform operators.
- [x] Scope restore operations and locks so a school restore cannot delete shared tables or another institution's rows.
- [x] Add supervised shared-schema migration rollout, version tracking, and failure reporting. Migrations run once globally, not once per institution.
- [x] Implement audited suspension/reactivation, domain changes, exports, retention, and archival/deletion rules.
- [x] Supply reviewed deployment templates for domains/HTTPS, database permissions, storage and worker processes; target-environment installation remains a launch prerequisite.

Deliverable: operational runbook. Done when tenant data restoration leaves other institutions intact and shared-schema upgrades have a verified maintenance/rollback procedure.

## Step 13 — Complete missing commercial modules

- [x] Use Step 1's audit to identify gaps in mobile, staff management, assignments, wallet, e-learning, mailing, CBT, timetable, and attendance capabilities.
- [x] Give each gap its own specification, implementation step, permission tests, and acceptance criteria.
- [x] Keep the wallet unavailable; specify separate ledger/reconciliation acceptance before any stored-value implementation.
- [x] Verify bearer API authentication and institution capabilities together; keep the dedicated mobile client deferred.
- [x] Update the signup catalogue to represent verified availability.

Deliverable: honest, complete tier offerings. Done when every advertised feature exists and is validated, or the launch catalogue explicitly reflects approved staged availability.

## Step 14 — Rehearse migration and launch

- [x] Rehearse a copy import with tenant_id, conflicting ID/link remapping and checked file copies. Original source defects are preserved separately in a rehearsal-only archive; production corrections remain unapproved.
- [x] Record delegated defaults: complimentary Silver, 2026-10-01 through 2027-01-31; verify explicit no-charge activation in the disposable rehearsal. Apply live only after import acceptance.
- [x] Verify import counts, relationships, users, payments, file hashes and scoped restoration. The original 31 missing references were subsequently reviewed and archived in the source correction; absent original files are not claimed as recovered.
- [x] Run regression plus focused catalogue, API, import, file-gate and restore checks. Record exact evidence in the step 13–14 acceptance report.
- [x] Document rollback and the last safe point before target writes/webhooks resume.
- [ ] Schedule cutover, take verified backups, pause relevant writers, migrate, validate, and resume service.
- [ ] Monitor the first live institution before opening broader signup.

Deliverable: functioning multi-tenant launch with verified recovery procedures. Done when the current institution is operating correctly and new institutions can select a plan, pay, provision, and use only their own permitted features.

## Immediate next task

Provision hosting for euvion.com.ng, complete production/gateway acceptance, then execute the reviewed cutover and first-school monitoring. Legacy schedules/teams, duplicate matric claims and unavailable references were resolved in the [legacy resolution](legacy-data-resolution-2026-10-18.md); preserve that private evidence during migration. See [module audit](commercial-module-audit.md), [Euvion launch runbook](euvion-launch-and-cutover.md), and [step 13–14 acceptance](multi-tenancy-launch-acceptance-2026-10-18.md). The legacy source was corrected under a verified backup; no live cutover to the shared platform has occurred.
