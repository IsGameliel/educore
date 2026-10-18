# Educore A–Z operating manual

**Version:** 18 October 2026. **Audience:** school owners, school administrators, staff, students and Educore platform operators.

This manual follows the current application from a school's first visit through ongoing school administration and platform oversight. Some screens still use the Euvion name. Instructions below refer to this project as Educore.

The URLs shown are for the local development installation. They are not public websites. Production hosting, public DNS, HTTPS and live-school cutover have not been completed. Features described as planned are not available merely because their navigation link exists.

## Quick directory

| Destination | Local address | Who uses it |
| --- | --- | --- |
| Public plans | http://educore.test:8000/pricing | A school comparing packages |
| Institution billing login | http://educore.test:8000/subscribe/login | The school's billing owner |
| Example school portal | http://rivers-college.educore.test:8000/login | That school's administrator, staff and students |
| Platform administrator login | http://educore.test:8000/dashboard/login | Educore platform staff |
| Platform plan management | http://educore.test:8000/dashboard/plans | Platform owner |

**The full journey:** choose a plan → create an institution billing account → review the fixed semester invoice → complete verified payment → confirm the school name, logo and brand colour → automatically enter the school dashboard → create academic structure/accounts → configure fees and registration → run teaching/results workflows → renew each semester. The platform team oversees institutions, packages, access and administrator security from its separate dashboard.

## A. Understand the three account areas

### Institution billing account

This account belongs to the school owner or person responsible for the school's subscription. It handles signup, subscription invoices, payment and next-semester renewal. Login asks for the institution web-address slug, billing email and password.

### School portal account

This is the institution's working system: students, staff, academic structure, fees, courses, results and school settings. Each institution has a separate hostname. Its users sign in on that school hostname, not on the central billing page.

### Platform administrator account

This belongs to you and authorized Educore employees. It manages packages, institution monitoring, access controls, platform administrators and platform audit history. A school's administrator is not automatically a platform administrator.

Signup initially creates a billing-owner account and a school administrator using the supplied email/password. They are separate accounts afterward. Do not assume changing the school administrator's password also changes the billing password.

## B. Prepare the local environment

For testing on this computer:

1. Start MySQL in XAMPP.
2. Open a terminal in the Educore project folder.
3. Run `php artisan tenancy:status` to check the available institutions.
4. If the application is not already running, run `php artisan serve --host=127.0.0.1 --port=8000` and leave the terminal running.
5. Confirm `educore.test` resolves to `127.0.0.1` in the Windows hosts file.
6. Add an entry for every newly registered school before opening its portal.

The hosts file is `C:\Windows\System32\drivers\etc\hosts`. Edit it with an administrator text editor. For the example school, add:

```text
127.0.0.1 educore.test
127.0.0.1 rivers-college.educore.test
```

Existing test portals are `foundation-alpha.educore.test:8000` and `foundation-beta.educore.test:8000`. Their manually provisioned payment exemptions differ from a newly self-registered school's paid subscription journey.

Local checkout requires a configured platform Paystack test key. School tuition/service checkout requires that school's own test key. External email requires a working mail transport. Public webhook delivery requires a reachable HTTPS address; a hosts-file entry alone cannot receive provider webhooks.

Do not run installation, migration or restore commands simply because a browser page fails. Shared-database upgrades follow the supervised operations procedure.

## C. A school visits Educore for the first time

1. Open the pricing page.
2. Read the available packages and their working features.
3. Compare the first-semester promotional price with the standard renewal price.
4. Select an available plan's signup action.

The established default catalogue is:

| Package | First-semester rate per student | Standard rate per student per semester | Main available scope |
| --- | ---: | ---: | --- |
| Silver | ₦1,000 | ₦2,000 | School portal, records, accounts, courses, fixed schedules and tuition/payment workflows |
| Gold | ₦2,250 | ₦4,500 | Silver scope plus academic results, appeals and transcripts |
| Platinum | ₦3,250 | ₦6,500 | Gold scope plus CBT, attendance and lecture materials |

These are catalogue defaults, not permanently fixed prices. The platform owner can edit rates. Use the current pricing screen and generated invoice as the actual terms.

Signup can be closed for a plan. A closed plan must be opened by the platform owner before new schools can register with it. Do not advertise closed signup as a functioning public onboarding route.

A dedicated mobile app, staff HR/payroll, assignments, wallet, bulk email campaigns, automatic timetable generation and full learning-management workflows are deferred. Basic staff accounts, manual schedules and a materials library should not be described as those larger products.

## D. Complete institution signup

Use the fictional school **Rivers College** as a walkthrough:

1. Choose Gold on Pricing.
2. Enter institution name: `Rivers College`.
3. Enter institution web address: `rivers-college`.
4. Enter the responsible administrator's full name and a working email address.
5. Choose and confirm a password of at least twelve characters.
6. Enter the semester name, for example `2026/2027 First Semester`.
7. Enter its actual start and end dates. For a current local example, use 18 October 2026 to 28 February 2027.
8. Enter students enrolled at semester start, for example `20`.
9. Tick the enrollment declaration after reviewing it.
10. Select **Create account and review payment**.

The web-address slug uses lowercase letters/numbers and single internal hyphens. It must be available; reserved names such as `admin`, `platform`, `api`, `www` and `mail` cannot be used.

The declared enrollment must be 1–100,000. Semester end must be after start, and the semester cannot exceed 366 days. Choose dates carefully because the issued invoice is fixed.

The enrolled count is a declaration, not an automatically certified count of imported accounts. A school can declare twenty students before importing their records.

## E. Understand what signup creates

The application saves the institution's central record and billing account, creates a semester subscription invoice, and attempts to provision its school administrator and initial school settings.

The school starts without example faculties, courses or students. Provisioning is account/setup preparation, not completed academic onboarding.

On the invoice screen check:

- Institution and semester name.
- Plan and included feature snapshot.
- Semester start/end dates.
- Declared enrolled count.
- Promotional or standard unit rate.
- Total and payment status.

At the default Gold promotional rate, twenty students cost **20 × ₦2,250 = ₦45,000**. If the next semester also has twenty students and the default standard rate remains unchanged, its total is **20 × ₦4,500 = ₦90,000**. This subscription policy adds no separate tax line.

The school's readiness and payment status are different. A school can finish setup and still await payment. Do not tell it that setup completion alone means its portal is active.

## F. Complete platform subscription payment

1. Review the invoice before checkout.
2. Select **Proceed to secure payment**.
3. Complete the provider checkout using test-mode facilities during local testing.
4. Return to the institution billing account.
5. Check for verified payment confirmation.
6. If the return was interrupted, use **Check payment status**.

The platform verifies the payment with the provider. A successful-looking browser return or a screenshot is not sufficient to activate a subscription.

If confirmation is pending, keep the existing reference and retry verification. Do not repeatedly pay because a callback was delayed. The application reuses pending checkout where appropriate; a server-verified failed/abandoned attempt may permit a fresh attempt.

Verified payment opens a new school's onboarding and dashboard immediately, even when the invoiced semester starts later. It does not override suspension or extend an invoice's end date. An existing current paid package keeps its feature permissions until the next semester.

## G. Resume interrupted signup or checkout

1. Open **Institution account login** at `/subscribe/login`.
2. Enter the slug `rivers-college`, the billing-owner email and signup password.
3. Review the saved invoice.
4. If setup failed or remains incomplete, enter the billing account password and select **Retry school setup**.
5. After setup succeeds, resume checkout or check payment status.

Do not register the same school again under another slug just to work around an interruption. The saved account and invoice are intended to support recovery.

School billing-owner password recovery is not the same as the new platform-administrator recovery screen. If the school cannot recover its billing login through the available interface, contact an authorized operator. Assisted school/billing recovery remains a later platform-interface phase.

## H. Open the school portal

Once payment is verified, the institution is active and the subscription has not expired:

1. Educore opens the institution onboarding form automatically.
2. Confirm the institution name, upload its logo and select a brand colour.
3. Select **Save and open my school dashboard**.
4. Educore signs the billing owner into that school's original active administrator account through a one-use handoff. Complete a school authenticator challenge if that account already has two-factor protection.
5. The school's own dashboard opens with its purchased feature permissions. Platform administration menus do not appear there.

If interrupted, sign back into the billing account to resume setup. Once complete, use **Open school dashboard** from billing, or sign in directly at `http://rivers-college.educore.test:8000/login`. Later branding changes use Institution Settings. See [the onboarding guide](institution-onboarding.md) for details.

For local testing, add the school hostname to the hosts file first. The application chooses the school from its registered hostname. Supplying another institution's name in a form does not switch the school.

Each school's records, settings and uploaded files are scoped to that institution. Platform administrators do not get a school impersonation button in this release.

## I. Set the school's identity and payment integration

### Institution settings

1. As school administrator, open **Administration → Institution Settings**.
2. Confirm the institution name.
3. Set the school's timezone. Signup currently starts with Africa/Lagos; choose the institution's intended timezone.
4. Enter contact email/phone.
5. Choose a brand colour and upload a logo.
6. Save, then check the dashboard and school login branding.

The settings apply to supported school pages and generated documents. Use the institution's actual logo rather than assuming a legacy university crest belongs to the new school.

### Payment integration

1. Open **Administration → Payment Integration**.
2. Enter the institution's own Paystack test secret key during testing.
3. Enter the current school administrator password.
4. Select **Save payment configuration**.
5. Copy the webhook and callback addresses displayed into the appropriate provider configuration when the school has reachable HTTPS hosting.

There are two payment relationships: the school pays Educore for its subscription, while students pay their institution for tuition/services. The platform subscription key and each school's payment key are separate. A school does not inherit the platform key if its own integration is missing.

## J. Build the academic structure

Work in this order:

1. Open **Academic Setup → Academic Sessions** or its dashboard panel and create the academic session.
2. Review activation for the session you intend to use. Inspect and explicitly select any eligible promotions before confirming.
3. Create faculties.
4. Create departments and assign their faculties.
5. Create the session's course offerings with code, title, credit units, department, level and semester.
6. Configure any course prerequisites.
7. On Gold/Platinum, configure department/session grading policies before entering marks.
8. Review promotion and registration settings.

A subscription semester is the school's commercial access period. An academic session/semester is part of the school's academic records. Paying the commercial invoice does not create academic courses or promote students.

Activating a session is a reviewed workflow. Renaming a session or uploading old results must not be used as a shortcut for changing student levels. Historical sessions need their own course offerings and saved student session levels.

## K. Create school staff and student accounts

### Staff

1. Open **Administration → Manage Staff**.
2. Create a staff record with a unique school email and appropriate role.
3. Complete password/confirmation fields where required.
4. Assign lecturers to the exact course records they teach.
5. Recheck assignments when a new academic session's courses are created.

Lecturers teach and submit within assigned scope. Exam officers handle authorized result administration. Bursars and accountants have finance responsibilities. A school administrator controls school setup; these school roles are separate from Educore's Owner/Finance/Support/Read-only platform roles.

### Students

1. Open **Students → Manage Students**.
2. Create or import records with correct names, email, department, level, matriculation number and entry year.
3. Use the import screen's expected format and review every skipped/error row.
4. Verify a sample of saved students before importing the rest.
5. Issue account access through the school's approved process and verify that any configured credential email actually arrives.

Do not assume creating an account means that student has completed admission payment, tuition clearance or course registration. Those are separate states.

## L. Configure student tuition and financial responsibilities

1. Confirm the active academic session and students' enrollment information.
2. Open **Finance → Tuition Fee Schedules**.
3. Create draft schedules for the appropriate department, level, student category and Annual/First/Second period.
4. Enter the fee breakdown, payment deadlines and any supported installment requirement.
5. Review and publish as school administrator.
6. Inspect generated invoices for sample students.
7. Use **Generate missing invoices** after fixing incomplete enrollment/imports where needed.
8. Enable the relevant tuition-clearance controls only after checking coverage.

Annual and semester schedules cannot overlap for the same cohort. A draft is not a published bill. Published schedule edits affect future invoices; issued amounts/deadlines remain fixed unless the existing adjustment workflow is used.

Entry year determines new/returning classification. Missing or invalid enrollment fields can prevent automatic invoice generation. The number of student tuition invoices is not the same as the declared enrollment on the platform subscription invoice.

Bursars can prepare unpublished schedules and relevant finance work. Accountants inspect payments/invoices and receipts. School administrators publish fees and decide authorized adjustments/exemptions. A waiver or exemption must follow the provided reason/approval rules; it does not mean money was received.

## M. A student starts using the portal

1. Open the school's portal hostname, not the central platform administrator login.
2. Sign in with the assigned student account or use school-enabled self-registration.
3. Complete email verification if prompted.
4. Complete the required admission/profile information and provide supporting documents where requested.
5. Open **Student Services** and resolve the onboarding checklist items relevant to that account.
6. Open **Fees & Payments → Tuition & Balance**, inspect the issued invoice, and pay through the school's checkout.
7. Check verified payment, balance and receipts before registering courses.
8. Update the profile photo and verify the matriculation number.

Students brought in as existing/imported students without an admission application are not automatically required to pay a fresh application fee. For a new application, follow its displayed payment requirement.

Where available, open **Temporary ID Card**. Downloads require the identification fields and image prerequisites shown. Card validity is retained from issuance; downloading again is not an automatic renewal.

## N. Control and complete course registration

### School administrator

Open **Academic Setup → Registration Settings** and review the independent controls:

- Whether course registration is open.
- Whether required tuition clearance is enforced for student registration.
- Whether the late-registration payment is required.

The current late-registration control requires **₦5,000 once per session and semester** when enabled. This is an explicit switch; do not assume an automatic calendar cutoff toggles it. Turning it off does not remove tuition requirements.

### Student

1. Open Course Registration.
2. Select/check academic session, semester and session level.
3. Resolve tuition and any required late-registration payment.
4. Select eligible courses and review credit/prerequisite messages.
5. Submit, then open Registered Courses and verify the saved list.
6. Download the registration PDF/Excel where offered.

Closing registration takes precedence over fee clearance. Existing registrations can remain viewable/downloadable. Admin-managed registration also has its applicable clearance rules.

For historical work, the administrator must save the student's level for the selected historical session. The portal does not infer it by subtracting years from the current level.

## O. Run teaching, attendance, materials and CBT

### Manual class schedules

Create schedules under **Teaching & Tests → Class Schedules** using the correct course, lecturer and time. Review conflict messages. This is manual scheduling with checks, not automatic timetable generation.

### Attendance — Platinum

Open Attendance as authorized staff, select the schedule/date and record Present/Late/Absent/Excused. In the scan workflow, students must use their eligible signed-in accounts. Codes expire after two minutes; generate/resend a current code if necessary.

### Lecture materials — Platinum

Open Lecture Materials, associate the upload with the correct course and save it. Verify access as an eligible student. This is a course-materials library, not a full assignments/progress/completion platform.

### Tests — Platinum

1. Create the test with course/department/level and duration.
2. Add questions, options, correct answers and marks.
3. Review the test and make it available through its provided controls.
4. Students start an eligible attempt, answer within the fixed deadline and submit.
5. Review responses as authorized staff.

The server preserves attempt timing and saved answers. Reopening the page does not reset the deadline. Later question edits do not rewrite a started attempt. CBT scores remain separate from official course-result publication.

## P. Enter, review and publish official results — Gold/Platinum

1. Confirm each student is registered for the exact course/session/semester.
2. Configure the relevant grading policy: component maxima totalling 100, pass mark, grade bands and applicable academic rules.
3. Open **Results → Enter Results** or the relevant registration's result action.
4. Enter CA and exam, or use the supported total-only path. Use an exceptional outcome for absent/incomplete/withheld situations rather than inventing a numeric zero.
5. Save the draft and inspect its grade and completeness checks.
6. Submit for review.
7. Review, approve and publish in sequence with authorized accounts.
8. Sign in as the student to confirm visibility after publication.

**Draft → Submitted → Reviewed → Approved → Published.** Students see published results; approval alone does not release them.

For bulk work, download the result-upload screen's template, retain its headings/course metadata, complete the roster and review skipped-row errors after upload. An uploaded workbook does not automatically publish results.

A school administrator may perform submission-stage review/approval on their own entries. An exam officer's own uploaded/submitted results require another authorized manager. A correction request still needs a different approver, even if the requester is an administrator.

Approved/published changes use the correction workflow and preserve history. Approved corrections require republication and can revoke affected official transcript documents. A resit is a separate authorized attempt, not an overwrite of the original result.

## Q. Handle appeals, transcripts and academic reports

### Appeals

The student selects the session/semester/course, explains the issue, attaches permitted evidence if needed and follows the displayed fee/payment step. Staff review the request and respond. Marking an appeal resolved does not itself change the result; use result entry/correction as appropriate.

### Official transcripts

The student submits a purpose/recipient request and completes its required payment. Authorized staff review complete published records and issue/reject with a reason. An issued document is available through the authorized download workflow; the portal does not automatically email/post it to the recipient merely because a recipient was entered.

Official documents have a registry verification link/reference. Revoked documents should not be presented as current. After a result correction, check whether reissuance is required.

### Reports and student services

School academic reports show workflow progress, grade distribution, standing, CGPA and configured eligibility information. These differ from the platform's planned Reports workspace.

Student Services provides request tracking and relevant onboarding/graduation-preparation checklists. An academic-eligibility checklist is not an automatic graduation, certificate or financial-clearance decision.

## R. Renew the institution's subscription

1. The billing owner opens **Plans & Renewal** in the school sidebar or signs in at `/subscribe/login`.
2. Review past/current invoices and outstanding payment.
3. Under **Renew for the next semester**, choose the next plan.
4. Enter its name, start/end dates and the new declared enrolled count.
5. Confirm the declaration and create the fixed invoice.
6. Review and complete verified payment.

The next semester starts after the preceding semester ends. The first-semester promotion is not repeated. Plan/count changes apply to the next invoice; this release does not prorate mid-semester changes or automatically debit the school.

For the worked example ending 28 February 2027, a next semester could start 1 March 2027. Paying it early does not switch the school's current features before that date.

There is no payment grace period. Once the last paid period expires, normal school-portal access is blocked, while the central billing account remains the route to history/payment/renewal. Records remain stored. Suspension/archival can still block a paid institution; contact the platform team to resolve its status.

## S. Export school data and understand recovery

1. As school administrator, open **Administration → School Exports & Restore**.
2. Enter the current password and select **Create school export**.
3. Review the export's ready status/expiry, then download it privately.
4. Before restore, read its effect on that school's records and files.
5. Enter the current password, type the required `RESTORE` confirmation with the export ID, and acknowledge the effect.
6. Restore only when intended, then sign in again and validate the school.

Restoration replaces that school's business records/local files, creates a safety export first, and revokes school sessions/tokens. It preserves current existing-account security settings. Other schools must remain unchanged.

Financial changes after export or a schema mismatch block self-service restore. Do not work around that refusal; request reviewed operator recovery. Cloud/S3 files and central subscription records are outside the school archive.

Current limits include seven-day export expiry and five ready exports per institution, plus archive size/row limits. Downloaded archives contain sensitive records and must remain private. A school export is not a full shared-platform disaster-recovery backup.

## T. Sign in to your platform administrator dashboard

1. Open `http://educore.test:8000/dashboard/login`.
2. Enter your separate platform administrator email/password.
3. If enabled, complete the authenticator/recovery-code challenge.
4. Open Overview.

Your current owner sidebar is **Overview · Institutions · Plans · Billing · Operations · Reports · Administrators · Audit Log · My Account**. Plans now has its own page. It is not managed inside Institutions.

Other roles see only permitted sections. Direct URLs enforce permissions as well as sidebar visibility. Use **Sign out** when finished on a shared machine.

Keep the existing local tester credentials in your private notes/chat rather than publishing them in a school-facing manual. The tester's `.test` mailbox is for local sign-in and cannot receive normal external recovery mail.

## U. Work with Overview and Institutions

### Overview

Review institution totals, registrations in the past seven days, awaiting-payment count, provisioning failures and confirmed subscription totals. Confirmed totals do not establish actual bank settlement or replace provider reconciliation.

### Find a school

1. Open Institutions.
2. Search by name/address slug.
3. Filter provisioning or subscription status if necessary.
4. Open the school name.

### Read the school detail page

Review owner contact, registered domain, plan, provisioning/access status, current student/staff account counts, semester invoices, payment attempts, provisioning history, temporary feature permissions where authorized, operations/task history and institution audit trail.

Account counts are operational records. They do not rewrite a subscription's declared enrolled count. A pending/failed setup, unpaid invoice, future paid semester and suspended school are different conditions and require different responses.

The platform detail page is an oversight view, not unrestricted access to that school's academic records. School impersonation is not implemented.

## V. Create and maintain plans on the dedicated Plans page

1. Sign in as platform Owner.
2. Select **Plans** in the sidebar.
3. Select **Create plan**.
4. Enter a unique lowercase plan code and display name.
5. Enter standard and first-semester prices in naira per student per semester.
6. Select the actual included features.
7. Decide whether to allow new signup for this plan.
8. Review and save. You return to Plans.

To update a plan, select **Edit** on Plans. Its code remains fixed. Review the signup flag separately from prices/features. Use **Cancel** to return to Plans without saving.

Prices must be positive and the promotional price cannot exceed the standard rate. Reserved signup-route codes are rejected. Only Owner has plan-management access.

Existing issued invoices retain their snapshotted rates and feature terms. A plan edit is not a way to retrospectively change what a paid school bought. Selecting a deferred module does not make its underlying implementation available.

## W. Manage institution lifecycle and feature exceptions

### Suspension, reactivation and archival

1. Open the school under Institutions as Owner or Support.
2. In **Portal access**, choose Active, Suspended or Archived.
3. Enter a meaningful reason of at least ten characters.
4. Confirm your password if requested and submit the access update again if the interface asks you to repeat it.
5. Review the institution audit trail and test the intended access outcome.

Suspension/archival block normal school access and revoke relevant credentials/work while retaining records. Reactivation does not waive outstanding subscription requirements. Archival is not permanent deletion and does not issue a refund.

### Domain replacement

Prepare the new hostname and its routing first. In **Primary portal domain**, enter the permitted Euvion subdomain or school-owned hostname and reason; confirm school authorization and DNS/HTTPS readiness only when verified. After saving, update provider callbacks/webhooks and published school links. Old portal links stop working and users must sign in again. School-owned hostnames are supported; automatic DNS configuration and certificate issuance are not provided. See [domain routing](domain-routing.md).

### Temporary feature permissions — Owner

Choose a supported feature, Allow/Deny, an expiry within ninety days and a reason. Record the permission and inspect the resulting history. Exceptions expire; they do not bypass payment, suspension or unavailable modules. A dedicated revoke interface remains future work; do not assume the history table includes one.

## X. Understand Billing, Operations and Reports today

These sidebar links currently open labelled landing pages:

| Section | Available today | Still planned |
| --- | --- | --- |
| Billing | Institution-specific invoice and payment-attempt history through Institutions | Global billing workspace and reviewed payment investigation tooling |
| Operations | Institution-specific health, task runs and lifecycle history | Central alerts, fuller failure handling and recovery visibility |
| Reports | Overview totals; existing school-specific reporting in school portals | Global date-filtered financial reports and exports |

There is no platform button to mark an unverified payment paid. There is no platform automatic-refund interface. Whole-platform restores remain supervised operator procedures. Permanent school deletion and impersonation require separate designs.

A quiet or empty task table does not prove the scheduler is healthy. The operator must configure and supervise institution workers/scheduler; the later global Operations workspace is not a substitute for that setup.

## Y. Manage platform administrators, audit history and your own security

### Platform roles

| Role | Current responsibilities |
| --- | --- |
| Owner | All current platform areas; plans/configuration, administrator management, feature overrides and central audit |
| Finance | Overview/institution details, Billing and Reports access; own account security |
| Support | Overview/institution details, existing school lifecycle/domain operations and Operations access; own security |
| Read-only | Permitted overview/institution/Billing/Operations/Reports views; no school/plan writes; own security |

The planned Finance and Support workflows do not become functional simply because those roles can open their landing pages. School-account recovery and assisted onboarding are later interface work.

### Invite a colleague

1. As Owner, open Administrators.
2. Enter the colleague's name, deliverable email and intended role.
3. Enter your current password and select **Send invitation**.
4. The colleague opens the emailed link within twenty-four hours and sets a password.
5. They sign in with their own account and verify the role's menu/access.

Do not share the owner's login as a substitute for creating separate accounts. Sending an invitation requires working mail delivery; a saved account alone does not prove the email arrived.

### Change access

Choose the administrator's role/status, enter a reason and your password, then save. Role/status changes revoke their sessions and pending setup/recovery links. Deactivation retains the account/history. The application refuses to demote/deactivate the last active owner.

For an active account, the recovery-link action can send a fresh password setup/recovery email. It preserves existing two-factor protection.

### Audit Log

As Owner, filter by event, administrator, exact subject ID and dates. Expand details to review reasons and relevant before/after values. Distinguish this central audit from the school's academic/financial audit and institution lifecycle trail.

Audit history starts with the feature's introduction. It does not fabricate earlier events. There is no dashboard edit/delete action for these records, but this is not an externally tamper-proof log against privileged database operators.

### My Account

- Change your password using the current-password and confirmation fields. The new password needs twelve or more characters, mixed case and a number. Other sessions are revoked.
- Start authenticator setup, enter the manual key in a time-based authenticator, and confirm a six-digit code within ten minutes.
- Save the eight displayed recovery codes privately. Each works once and is not redisplayed as plaintext later.
- On later sign-in, complete the second-factor challenge within five minutes.
- Inspect signed-in sessions and revoke another device using your password. A revoked session fails on its next protected request; already-running work is not cancelled.
- Disable the authenticator only through its password-plus-factor form if intended.

Sensitive writes need recent password confirmation. Forms that request your password can confirm directly; other actions can redirect to confirmation and then return you to repeat the action. Confirmation lasts ten minutes.

Use **Forgot your password?** on the platform login for a one-hour recovery link. Resetting the password revokes prior sessions and retains existing authenticator protection. It does not bypass lost two-factor credentials; keep the recovery codes.

## Z. Run the service, troubleshoot and complete a practice journey

### Daily responsibilities

**School administrator:** check enrollment, registrations, fee problems, staff assignments and pending academic requests. Resolve incomplete records before bulk imports or result publication.

**Finance staff:** inspect pending payments, balances, exceptions and reminder failures. Use verified provider evidence; do not treat payment screenshots as ledger entries.

**Lecturers/exam officers:** check rosters, complete drafts, review authorized submissions and publish only after completeness checks.

**Platform team:** inspect new/failed institutions, paid-but-blocked access, approaching commercial renewals, provisioning failures, task health and platform security/audit events. Keep operator backups and mail/gateway checks current.

### Semester-end responsibilities

Finish academic publication/corrections, preserve historical sessions and levels, prepare new courses and assignments, agree subscription dates/enrollment/plan, create and verify renewal payment, and check new-semester access. Promotion, fee schedules and subscription renewal are separate workflows.

### Troubleshooting

| Symptom | First checks |
| --- | --- |
| Local site does not open | MySQL/app server running, correct port and hosts-file mappings |
| Newly created school hostname fails | Add its exact local mapping; verify registered domain |
| Unknown-host 404 | Use a registered school or central hostname |
| Signup unavailable | Owner must check the plan's signup flag |
| School setup failed | Resume billing account and use Retry school setup; operator reviews provisioning history |
| Checkout cannot start | Correct platform key for subscription or school key for student payments; test/live configuration |
| Paid but portal blocked | Verified invoice, subscription expiry, setup readiness and suspension/archival status; a future start date alone does not block paid access |
| Subscription recovery email absent | Verify the account area; school billing recovery is not platform-admin recovery |
| Platform invitation/recovery email absent | Deliverable email, active account, mail transport, spam folder, expired/superseded link |
| Expected sidebar item missing | Account role, school plan permissions and mobile menu disclosure |
| Tuition invoice absent | Active session, published matching fee schedule, department/level/entry year and missing-invoice generation |
| Registration blocked | Registration switch, tuition clearance, late fee, selected session/level and course eligibility |
| Result absent for student | Exact registration/session and Published stage; approved alone is insufficient |
| Import partly succeeded | Review skipped-row errors, identifiers, session/semester and required roster |
| School restore refused | Financial changes, schema mismatch, archive expiry/quota or account eligibility; ask an operator |
| Platform returns to login | Session revoked, role/security change, deactivation or expired browser session |
| Authenticator rejected | Device clock, correct account, fresh code, challenge timeout or unused recovery code |
| Requests return 429 | Stop repeated attempts and wait for the rate-limit window |

### Complete practice journey

Use a disposable test institution and provider test mode. Do not perform this exercise on a live school's financial records.

1. As platform Owner, inspect the intended plan on Plans and allow test signup if appropriate.
2. Register the fictional school with twenty students and a current semester period.
3. Check the fixed invoice and complete real provider test-mode checkout when the test integration is configured.
4. Add the local school hostname, sign in and configure branding.
5. Configure the school's own test gateway.
6. Create a session, faculty, department, course, lecturer and student.
7. Publish an appropriate tuition schedule; inspect/pay the student's test invoice and verify clearance.
8. Register the student for the course.
9. On Gold/Platinum, enter CA 24 plus exam 46 under a 30/70 policy, then submit, review, approve and publish. Confirm the student sees 70 after publication.
10. On Platinum, test one material, attendance session and short CBT; confirm CBT does not automatically publish an official course result.
11. Open the institution in the platform dashboard and review its status, invoice, payment attempt and counts.
12. Suspend the disposable school with a reason; confirm access is blocked. Reactivate and verify access, subject to its paid period.
13. Inspect the audit history, then test your own administrator security and a colleague's restricted role using separate accounts.
14. Review the next-semester renewal form without issuing an unintended invoice. Export the test school's records if needed.

### Supporting references

- [Detailed school portal workflows](portal-user-manual.md).
- [Platform security and role acceptance](platform-administration.md).
- [Semester subscription policy](plans-semester-billing-and-onboarding.md).
- [Institution integrations, exports and operator procedures](multi-tenancy-operations.md).
- [Tuition billing](tuition.md) and [finance automation](finance-automation.md).
- [Academic results](academic-results.md) and [historical student levels](student-session-levels.md).
- [Deferred commercial scope](commercial-module-audit.md).

This manual documents the current local release. Real SMTP delivery, publicly reachable provider test-mode webhooks, production hosting and live-school cutover need their separate environment acceptance.
