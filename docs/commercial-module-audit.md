# Commercial module audit and staged release

Reviewed 18 October 2026. Step 13 uses the plan's staged-availability option. A complete mobile client, assignments, stored-value wallet, staff HR, bulk campaigns and automatic timetable generation are excluded from this release. No delivery dates are promised. This is not a claim that those modules have been implemented.

## Available release scope

| Tier | Working scope | Evidence |
| --- | --- | --- |
| Silver | Role-based portals, student/admission records, faculties/departments, branding, courses/registrations, manual class schedules with conflict checks, tuition/Paystack, user and staff accounts, academic and institution settings | AcademicAutomationTest, CourseManagementTest, StaffDashboardTest, TuitionTest, TenantFoundationTest |
| Gold | Silver plus results, grading, appeals, corrections and transcripts | AcademicWorkflowTest, ResultAuthorizationTest, StudentServicesTest, TenantFoundationTest |
| Platinum | Gold plus course materials upload/filter/download, CBT and attendance | CourseMaterialFilterTest, TestTakingTest, TestQuestionManagementTest, AcademicAutomationTest, TenantFoundationTest |

Course materials are a library, not a complete learning platform. Manual conflict checks are available in Silver; an automatic scheduling solver is not available. Staff account administration remains in Silver; payroll, employment and leave workflows are not included. Transactional account/payment notices remain essential services; bulk mailing is excluded. Responsive browser access is available; it is not a packaged mobile application.

LaunchCatalogue maintains the reviewed feature names, implementation status and precise scope. Fresh and existing platform catalogues receive the review through initialization and a forward central migration. Signup/pricing show unavailable products separately, without included-feature checkmarks. New semester invoices snapshot only available scope. Previously issued invoice snapshots and prices are preserved. FeaturePermissions denies unavailable modules even with a platform override.

GET /api/institution/features requires institution resolution and a Sanctum token. It returns server-computed feature capabilities for that institution, with private/no-store caching. Acceptance covers unauthenticated denial, another school's token rejection, Silver/Platinum differences and disabled modules. The API remains a foundation; it does not implement a complete mobile product or module APIs.

## Deferred specifications and acceptance requirements

| Work item | Specification and implementation sequence | Required permission and acceptance tests |
| --- | --- | --- |
| M1 — Mobile application | Define supported devices and workflows; design authenticated student/staff screens, tenant-domain enrollment, token revocation and version compatibility; build a client only after corresponding APIs exist. | School A's token never works on B; revoked/deactivated tokens fail; each read/write endpoint enforces the same tier as the web app; no secrets in app builds. |
| M2 — Staff HR | Define employment profiles, restricted HR documents, positions and leave approval. Implement separate HR roles and audited workflows. Payroll requires a separate specification. | Gold access required; ordinary Silver staff-account CRUD continues; unauthorized staff cannot view HR files; cross-school IDs and approvals denied. |
| M3 — Assignments | Add course-linked assignments, published deadlines, student submissions, authorized files, rubric marking and feedback; define resubmission and late policy before implementation. | Gold required; enrolled students submit only their own work; assigned lecturers mark only their courses; deadlines/timezones, repeated submissions and private files tested. |
| M4 — Wallet | Define funding, ownership, currency, withdrawals and accounting rules. Implement an immutable balanced ledger with atomic posting, provider reconciliation and idempotent external references before exposing balances. | Keep disabled until approved. Duplicate callbacks cannot credit twice; concurrent debits cannot overspend; reversals preserve ledger history; reconciled totals match provider records; every account and posting stays within its institution. No wallet balance is derived from tuition invoice credit. |
| M5 — Full e-learning | Keep current materials library. Specify lessons, enrollment, progress and completion; add each workflow separately with its own data model and access checks. | Platinum required for new learning workflows; Gold cannot bypass via direct URLs/API/jobs; inaccessible content is never exposed through storage URLs. |
| M6 — Bulk mailing | Specify opt-in targeting, sender verification, unsubscribe/suppression, rate quotas and delivery events. Add preview/approval and tenant-aware jobs before enabling campaigns. | Platinum required; recipients and templates cannot cross schools; retries do not duplicate sends; unsubscribed recipients remain excluded; transactional security notices remain independent. |
| M7 — Automatic timetable | Define rooms, lecturer availability, required periods, conflict priorities, generation and reviewed publication. Add a deterministic solver and versioned proposals; retain manual schedules. | Platinum required for generation; Silver keeps manual schedules/conflict checks; impossible constraints yield a reviewable failure; parallel generation cannot overwrite another school's schedule. |
| M8 — Extend existing CBT/attendance | Current timed attempts, saved answers, scoring, attendance sessions and scans remain available. Treat proctoring, offline sync and biometric devices as separate future specifications. | TestTakingTest and AcademicAutomationTest remain regression gates; tier, course assignment, student identity, time boundaries and two-school isolation must continue passing. |

Each deferred item stays unavailable until its implementation, permissions and acceptance evidence are reviewed. The scope in this document supersedes the earlier broad marketing descriptions for new signup; it does not silently rewrite historical agreements.
