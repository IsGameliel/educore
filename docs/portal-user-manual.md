# EduCore portal user manual

This manual describes the features currently implemented in this project. Menu options depend on your account role. Examples use a fictional student, Ada, and a course, CSC101. Substitute your institution's actual students, courses and academic sessions.

For deployment commands and technical upgrade notes, use [Academic result management](academic-results.md). This manual is for day-to-day portal use.

## Contents

1. [Accounts and roles](#1-accounts-and-roles)
2. [First-time institutional setup](#2-first-time-institutional-setup)
3. [Student records and admission information](#3-student-records-and-admission-information)
4. [Course registration](#4-course-registration)
5. [Grading policies](#5-grading-policies)
6. [Entering results](#6-entering-results)
7. [Uploading a result workbook](#7-uploading-a-result-workbook)
8. [Submission, review, approval and publication](#8-submission-review-approval-and-publication)
9. [Corrections and change history](#9-corrections-and-change-history)
10. [Resit examinations](#10-resit-examinations)
11. [Carryover, repeat standing and CGPA](#11-carryover-repeat-standing-and-cgpa)
12. [Student result viewing and appeals](#12-student-result-viewing-and-appeals)
13. [Transcripts and verification](#13-transcripts-and-verification)
14. [Academic reports](#14-academic-reports)
15. [Schedules, attendance, materials and tests](#15-schedules-attendance-materials-and-tests)
16. [A complete practice exercise](#16-a-complete-practice-exercise)
17. [Daily and end-of-session routines](#17-daily-and-end-of-session-routines)
18. [Troubleshooting](#18-troubleshooting)
19. [Current boundaries](#19-current-boundaries)
20. [Database backups and restoring](#20-database-backups-and-restoring)

## 1. Accounts and roles

### Signing in

1. Open the portal address supplied by your institution.
2. Sign in with your assigned email and password, or use the registration page if your institution permits student self-registration.
3. If prompted, enter the email verification code. Use the resend option if the code does not arrive; check your spam folder too. Repeated verification attempts are rate limited.
4. Complete the admission-information form if your account is directed there.
5. Open **Dashboard**. Use **Profile** to manage the account settings available to you.
6. Log out when you finish on a shared computer.

Ask the administrator to correct your role if the expected menu is missing. Do not use another person's credentials to perform a workflow action: the portal records the account that performs it.

### Who can do what?

| Task | Admin | Exam officer | Lecturer | Student |
| --- | --- | --- | --- | --- |
| Manage institutional structure, accounts and course registrations | Yes | Not through the admin management screens | No | Own course registration only |
| Enter/import and submit results | Yes | Yes | Assigned courses | No |
| Review and approve results | Yes, including their own submissions | Other staff's submissions; cannot review/approve their own uploaded or submitted results | No | No |
| Publish approved results | Yes | Yes | No | No |
| Authorize a resit | Yes | Yes | No | No |
| Manage grading policies and academic reports | Yes | Yes | No | No |
| Resolve appeals and issue official transcripts | Yes | Yes | No | No |
| View student results | Management access | Management access | Assigned course scope | Own published results |

**Admin exception:** an admin may complete Submit → Review → Approve for the same result themselves. This also applies when the admin uploaded the result. Every stage and completeness check still applies.

**Other staff:** an exam officer who uploaded or submitted a result must ask another authorized admin/exam officer to review and approve it. A lecturer submits assigned results for an admin/exam officer to review and approve. Being a different lecturer does not grant approval authority.

Other staff roles may have a dashboard and profile, but they do not automatically have academic approval permissions.

## 2. First-time institutional setup

Perform these steps as admin before entering real results.

### Step 1: Create the academic session

1. Open **Dashboard**, then the **Academic Sessions** panel. The sidebar also links to that panel under **Courses & Structure**.
2. Create the session, using the session form's year fields.
3. Activate the session that should be used for current registration.
4. Retain earlier sessions for historical records.

Always check the session filter when working with courses and results. CSC101 in one session is not automatically the same course record as CSC101 in another session.

### Step 2: Create faculties and departments

1. Open **Courses & Structure → Faculties** and create the faculties.
2. Open **Departments** and assign each department to the appropriate faculty.
3. Confirm names and codes before importing accounts or courses.
4. Use the available import forms for larger lists and follow each form's required format. Do not use the result workbook format for other imports.

### Step 3: Create courses

1. Open **Courses & Structure → Courses**.
2. Create a course with its code, title, credit units, department, level, semester and academic session.
3. Check that the session and semester are correct.
4. Open the course's prerequisites screen if prerequisite courses need to be assigned.
5. Repeat for every course offered in that session.

For shared courses, ensure the appropriate department course records exist. Result imports require the student's matching course registration; a shared course name alone does not enroll a student.

### Step 4: Create staff and assign lecturers

1. Open **Staff** and create or edit a staff record.
2. Enter the name, email, role and other requested details.
3. When creating or changing a password, complete the confirmation field. The management forms require at least eight characters.
4. For a lecturer, use **Assigned Courses** to select the courses they teach.
5. Check assignments again when a new session's course records are created.

An assignment for last session does not grant access to every later occurrence of the same course code.

### Step 5: Configure policies and register students

Create the department/session grading policies described in section 5. Then create student records and course registrations before entering marks.

## 3. Student records and admission information

### Creating or updating students

1. Open **Students** and select the student-management screen.
2. Create a student or open an existing record for editing.
3. Confirm the name, email, department, level and matriculation number.
4. Save and check that the student appears in the list.
5. For bulk creation, open the student import form and follow its column instructions.

The matriculation number identifies students in result workbooks. A misspelled number can prevent a row from importing even when the student's name is correct.

### Completing admission information as a student

If the portal prompts you to complete admission information:

1. Enter your personal and contact details.
2. Enter parent/guardian contact information.
3. Select the admission type: fresh, direct entry, transfer or foreign.
4. Select the department, level and entry year.
5. Enter the previous school and qualification.
6. For O-level qualifications, supply the subjects and corresponding grades.
7. Attach supporting documents where applicable. The admission form accepts PDF/JPG/PNG documents up to 5 MB each for its document fields.
8. Review the details and submit.

### Reviewing submitted information as admin

Open **Students → Admitted Students**, select a record, and inspect the details and supporting documents. In the current implementation this list contains completed admission-information forms. A completed form is not a separate admission-offer approval decision.

## 4. Course registration

### Student registration

1. Open the course-registration menu.
2. Check the active academic session, semester and selected level.
3. Select the available courses you need to register.
4. Review prerequisite messages and the total credit load.
5. Submit the registration.
6. Open your registered courses, select the session/semester, and verify the saved list.
7. Use **Download PDF** or **Download Excel** when you need a registration copy.

The current registration limits are 24 credit units for levels 100–400 and 30 for other levels handled by the fallback rule. The portal's prerequisite check uses registration history; it is not a complete prerequisite-pass verification system.

### Admin registration management

1. Open **Students → Course Registrations**.
2. Select the session and find the student by name, email or matriculation number.
3. Click **Courses & Results**.
4. Select the required semester and session.
5. Click **Edit Courses**, select the courses, set their registration statuses and save.
6. Return to the registered-course list to check the result.

| Registration status | Can receive a result? |
| --- | --- |
| Registered | Yes |
| Approved | Yes |
| Completed, where already present | Yes |
| Pending | No |
| Rejected | No |
| Withdrawn | No |

A result needs exactly one eligible registration for that student, course, session and semester. Registering a course does not create a zero score or publish a result.

Once result history exists, the registration cannot be deleted, withdrawn, rejected or reassigned to a different student/course/session. This also protects history belonging to deleted drafts. Resolve mark problems through the result workflow.

## 5. Grading policies

Open **Academic Records → Grading policies** as admin or exam officer.

1. Select the department/programme and academic session.
2. Set **CA maximum** and **Exam maximum**. Their sum must be 100; for example, 30 and 70.
3. Set the pass mark, for example 40.
4. Check the minimum score, letter and grade point for each grade band.
5. Choose the CGPA repeat rule: all, latest or highest graded attempt.
6. If graduation reporting is required, enter the required credits, minimum CGPA and required course codes.
7. Click **Create new policy version**.

The grade-point scale is 0–5. Verify that the policy matches the institution's approved rules before entering marks.

### Changing a pass mark later

A new policy does not automatically rewrite old results. Each result keeps a policy snapshot.

- For a draft, open it and use **Adopt the latest grading policy for this department/session**, then save with a reason.
- For an approved/published result, request a correction and select the option to propose regrading with the latest policy. Another authorized manager must approve that correction.
- The older **Department Pass Marks** screen changes the fallback used when no versioned policy applies. Use **Grading policies** for deliberate department/session policy versions.

The repeat threshold remains ten outstanding failed courses; changing a pass mark changes which results fail only when the relevant grading policy is applied.

## 6. Entering results

### From an existing registration — admin

1. Open **Course Registrations → Courses & Results** for the student.
2. Select the correct session and semester.
3. In the course's **Results** column, click **Enter result**.
4. Verify the student, course and credit units shown.
5. Choose an outcome.
6. Enter either both CA and exam marks, or a total score with both component fields blank.
7. Click **Save draft result**.
8. Open the saved result from the registration page or **Academic Records**.

### From Academic Records — authorized staff

1. Open **Academic Records → Results → Enter result**.
2. Select the course and check its session, semester and department.
3. Select the student from the eligible registered-student list.
4. Enter the outcome and marks.
5. Save and inspect the calculated score and grade.

Lecturers see courses within their assigned scope. Exam officers use Academic Records for entry rather than the admin-only registration-management pages.

### Example

With a 30/70 assessment policy, enter CA **24** and exam **46**. The total is **70**. Do not enter CA 35 under a policy whose maximum is 30. A missing CA or exam is not automatically treated as a genuine zero: supply both components, use a total only, or choose an appropriate exceptional outcome.

### Outcomes

| Outcome | Use |
| --- | --- |
| Graded | Valid numeric assessment marks, including a genuine zero |
| Absent | Student was absent |
| Incomplete | Assessment is incomplete |
| Withheld | Result is being withheld |
| Deferred | Assessment has been deferred |
| Withdrawn | An authorized academic withdrawal outcome |
| Not submitted | Marks have not yet been supplied |

Exceptional outcomes have no numeric grade. They are not substitute failing scores. Incomplete and not-submitted records must be resolved before course approval/publication can pass completeness checks.

## 7. Uploading a result workbook

1. Open **Academic Records → Import workbook**, or the result-upload page for your role.
2. Choose the session, semester and course or courses.
3. Click **Download template**.
4. Open the workbook. Each selected course has its own worksheet and registered-student roster.
5. Enter marks in the blank cells. Keep the course metadata and headings intact; the generated table begins on row 8.
6. Check matriculation numbers. The name column helps you check the roster but does not replace the matriculation number.
7. Save and upload the workbook using the matching session, semester and course selection.
8. Read the created/updated/skipped summary and any row errors.
9. Verify the imported drafts before submitting them.

Imports require active registration and valid numeric marks. A locked result must use the correction process. Workbook imports update the primary exam attempt; enter authorized resit marks on the separate resit record.

Do not assume an upload succeeded completely just because the request finished. Resolve skipped rows before seeking approval. For absent, withheld or other exceptional outcomes, use the individual result form rather than typing those words into numeric spreadsheet cells.

## 8. Submission, review, approval and publication

The sequence is:

**Draft → Submitted → Reviewed → Approved → Published**

| Stage | Meaning | Student visibility |
| --- | --- | --- |
| Draft | Marks are being entered and checked | Hidden |
| Submitted | Ready for formal review; ordinary editing is locked | Hidden |
| Reviewed | Reviewer has checked the submission | Hidden |
| Approved | Authorized approval recorded | Hidden |
| Published | Released to the student | Visible |

### Acting on one result

1. Open **Academic Records**, filter the list, and click **Open v…** for the result.
2. Inspect marks, outcome, policy and **Completeness checks**.
3. In **Workflow**, choose the next action.
4. Enter a meaningful reason or review note, at least five characters.
5. Click **Record action**.
6. Repeat the required stages with the appropriate account.

Admin may submit, review and approve their own result. Exam officers must hand their own uploaded/submitted results to another authorized admin/exam officer for review and approval. Lecturers submit, then hand over to an authorized manager. Publication is a separate action available to admin and exam officers.

### Working with several results

1. Filter results to the intended department/session/semester/stage.
2. Select individual rows or the select-all checkbox for the displayed rows.
3. Choose **Action for selected results**.
4. Enter review notes and click **Apply to selected**.
5. If any selected record fails validation, the batch rolls back. Resolve the issue and retry.

Admin's self-approval exception also applies to batches. An exam officer cannot bypass the different-reviewer rule by using a batch.

### Completeness checks

Before approval/publication, resolve missing registered candidates, unregistered candidates, invalid or missing registration links, duplicate attempts, missing marks, unresolved incomplete/not-submitted outcomes and missing policy snapshots. Checks cover the relevant course roster, so a problem affecting another student in the same course can block an individual approval.

Admin can return submitted/reviewed results to draft with a reason. Exam officers can also perform that return action. Correct the draft, resubmit, then complete the workflow again.

## 9. Corrections and change history

For an approved or published result:

1. Open the result and find **Request a correction**.
2. Enter the proposed marks/outcome/credits and explain the change.
3. If a policy change is intended, an authorized manager can select the regrading option.
4. Click **Submit correction for approval**.
5. A different authorized admin/exam officer reviews the proposed values in **Corrections**, selects Approve or Reject, and supplies a decision reason.
6. If approved, verify the new version and publish the result again.

Approval of a correction returns the record to Approved, removes its published status until republished, and revokes affected transcript documents. The old values remain in **Change history**, with the actor, time, reason and approver.

**The admin self-review exception applies to submission stages. Correction requests still require a different approver, including corrections requested by admin.**

Do not alter an existing exam into a resit. A resit is a separate attempt.

## 10. Resit examinations

As admin or exam officer:

1. Open the student's **published, graded, failed original exam result**.
2. Find **Authorize resit exam**.
3. Enter the authorization reason and click **Authorize resit**.
4. The portal creates a separate resit draft linked to the original exam and its course registration.
5. Enter the new marks in the resit draft. Assigned lecturers may enter and submit them too.
6. Submit, review, approve and publish through the normal role rules.
7. Open **Exam attempts** to compare the original and resit.

Example: Ada's original score is **32**, and the resit score is **58**. Both records remain. Until the resit is published, the student sees only the published original attempt. Once the passing resit is published, the course is no longer an outstanding failure.

There is no need for a second registration for a same-session resit. The current implementation supports one separate resit per course/session/semester. Resits are individually authorized, not automatically created for every failure.

## 11. Carryover, repeat standing and CGPA

- Ten or more outstanding failed course codes in the selected academic session produce **Repeat** standing.
- One to nine produce **Carryover** standing.
- A subsequently passed course is no longer outstanding.
- Multiple failures of the same course do not represent multiple distinct failed course codes.
- Exceptional outcomes are tracked separately rather than converted into failures.

The standing is an academic flag. It does not automatically move a student to another level, change enrollment or register the next session's courses.

### How repeats affect CGPA

| Policy | Treatment of graded attempts |
| --- | --- |
| All | Counts every graded attempt; this is the default |
| Latest | Uses the latest graded attempt for each course |
| Highest | Uses the highest graded attempt for each course |

CGPA uses credit-weighted grade points: total counted quality points divided by counted credit units. For example, grade point 4 in a three-credit course gives 12 quality points. The repeat policy determines which attempts contribute. Passing a resit clears the outstanding failure but does not erase the original score.

Only published results contribute to displayed academic standing. Historical semester views exclude results from later semesters.

## 12. Student result viewing and appeals

### Viewing results

1. Open **Academic Records → Results** or your student results menu.
2. Select the relevant session and semester.
3. Open a result to inspect its published outcome and marks.
4. You can also open **Registered Courses** and inspect **Published results** beside each course.

**No published result** means nothing has been released for that registration. It does not mean the student scored zero or failed.

### Reporting a problem

1. Open **Academic Records → Appeals**.
2. Select the session and semester and enter the course code.
3. Explain the issue, at least ten characters, with enough detail for staff to investigate.
4. Optionally attach PDF/JPG/PNG evidence up to 5 MB.
5. Submit and return to the same page to track status and staff responses.

A missing result can be appealed even when no result record exists.

### Handling appeals as staff

Admin/exam officers open **Appeals**, inspect the message/evidence, and set **In review**, **Resolved** or **Rejected**, with a response. Resolving an appeal does not itself change marks: make the necessary draft entry or approved correction separately.

## 13. Transcripts and verification

### Unofficial student copy

Use the semester result page's transcript-generation/download action where shown. A student copy is not the official issuance process.

### Requesting an official transcript

1. Open **Academic Records → Transcripts**.
2. Enter **Purpose / recipient** and submit the request.
3. A manager requesting on a student's behalf must also enter the student's internal numeric ID. This is not the matriculation number.
4. Track the request on the same page.

### Issuing an official transcript

1. As admin/exam officer, open the pending request.
2. Confirm that the student's records are complete and published and resolve blocking exceptional outcomes.
3. Select **Issue official transcript**, enter the reason and record the decision, or reject the request with an explanation.
4. Find the document under **Issued documents** and use **Download PDF**.

Documents are stored privately and downloads check access. Requests record their purpose/recipient, but do not assume the portal automatically dispatches the PDF to that recipient.

### Verification and revocation

Official PDFs include a verification reference and URL. **Verify document** shows whether the issued document is valid or revoked without publishing the student's grades. Verification also exposes the document's SHA-256 fingerprint; this is portal-registry verification, not a cryptographic PDF signature.

A manager can revoke a valid document with a reason. Corrections to underlying results also revoke affected documents. After the corrected results are republished, follow the issuance process for a current official transcript.

## 14. Academic reports

As admin or exam officer, open **Academic Records → Academic reports**.

1. Choose the department and session, and the semester if relevant.
2. Inspect submission progress by workflow status.
3. Check grade distribution and pass/fail figures.
4. Review the departmental broadsheet and each student's standing, CGPA, earned credits and outstanding courses.
5. Resolve listed missing marks and completeness problems.
6. Use **Export academic standing (CSV)** when available.

Pass/fail figures describe graded attempts, not necessarily unique students. Graduation eligibility considers configured credits, CGPA, required courses and unresolved academic records. **Requirements not configured** means the necessary policy settings have not been supplied; it is not a graduation decision. Academic eligibility does not certify financial or administrative clearance.

## 15. Schedules, attendance, materials and tests

### Class schedules

Admin opens **Teaching Delivery → Class Schedules**, creates the class using the course/lecturer and timing fields provided, and checks the saved schedule. Students use their schedule menu to view relevant classes.

### Attendance

Admin or an authorized lecturer opens **Attendance**, selects the class schedule and date, and records each student's status: Present, Late, Absent or Excused. Save and inspect the attendance summary.

The scan-code workflow generates and emails a code to eligible students. Codes expire after two minutes. Students must sign in using an eligible account and open the scan link before expiry. If it expires, staff should generate/resend a current code from that attendance session.

### Course materials

Admin opens **Teaching Delivery → Course Materials**, enters the requested course details, uploads the PDF and saves. The material upload accepts PDFs up to 20 MB, with an optional cover image. Students open their course-materials menu to access relevant material.

### Online tests

1. As admin or an authorized lecturer, open the tests-management menu.
2. Create the test using the subject/course, department, level and duration fields.
3. Open question management and add the question text, answer options, correct option and marks.
4. Check the questions before students attempt the test.
5. Students open their tests list, start the test, answer the questions within the displayed time and submit.
6. Staff open the test's responses screen to review attempts.

The portal has retake-prevention behavior for submitted tests. Online test scores are stored separately from official course results. Do not assume completing a test automatically creates, approves or publishes a course result; use the academic result workflow to record official marks.

## 16. A complete practice exercise

Use clearly identified practice accounts and an appropriate test environment.

1. Create a session, faculty, department and three-credit CSC101 course for First semester.
2. Create student Ada and lecturer Ben; assign Ben that exact course record.
3. Create an exam-officer account and retain an admin account.
4. Register Ada for CSC101 with Registered or Approved status.
5. Create a department/session policy with CA 30, exam 70 and pass mark 40.
6. As Ben, enter CA 12 and exam 20, producing a failed total of 32. Save the draft and submit it.
7. As Ada, verify that no result has been published yet.
8. As the exam officer or admin, review, approve and publish the result.
9. As Ada, verify that 32/F now appears beside CSC101.
10. As admin/exam officer, authorize Ada's resit with a reason.
11. Enter CA 20 and exam 38 for the separate resit, producing 58. Submit and complete review, approval and publication.
12. Confirm both attempts remain and CSC101 is no longer outstanding.
13. Submit an appeal and inspect the staff response workflow.
14. Request an official transcript. Issue it after resolving all records in its scope and open its verification page.
15. For a separate practice result, use admin to submit, review and approve their own entry. Repeat with an exam officer and confirm self-review/self-approval are blocked.
16. For an approved result, request a correction, have a different manager approve it, and confirm a new version is recorded and publication is required again.

## 17. Daily and end-of-session routines

### Admin

Confirm the active session, student identifiers, course records and staff assignments. Resolve registration problems before marks entry. Monitor publication, appeals and transcript requests. Ask the technical administrator to maintain database and private-document backups.

### Lecturer

Check assigned courses and registration rosters. Download a fresh result template, enter and verify marks, resolve row errors and submit complete course results. Follow up on returned drafts. Record resits only on authorized resit attempts.

### Exam officer

Monitor submitted results, review other staff's submissions, resolve completeness issues, approve and publish authorized records, and handle resits, appeals and transcript requests. Ask another authorized manager to review/approve any result you uploaded or submitted.

### Student

Verify course registration after enrollment, monitor published results, report discrepancies through Appeals, and request official documents through Transcripts.

### End of session

Check missing submissions, complete approvals/publication, review outstanding courses and graduation reports, resolve appeals and corrections, and confirm the next session's courses and lecturer assignments. Preserve historical sessions and records.

## 18. Troubleshooting

| What you see | What to check or do |
| --- | --- |
| Student is missing from result-entry list | Confirm an active registration for the exact course, session and semester; check department and identifiers. |
| No courses available | Check course department, level, semester and academic session. For lecturers, check assigned courses. |
| Student not found during import | Compare the workbook matriculation number with the saved student record. |
| Registration required during import | Enroll the student in the matching department course and session before retrying. |
| Score rejected | Check policy maxima; provide both CA and exam or a total only. Use an exceptional outcome for a non-numeric situation. |
| Result already exists | Open the existing draft, or request a correction if locked. Do not create a duplicate primary exam. |
| Another authorized staff member must review and approve | For an exam-officer/lecturer submission, hand over to an authorized admin/exam officer. Admin is exempt for submission-stage self-review/approval. |
| This result must be submitted/reviewed/approved first | Complete the stages in order; approval and publication are distinct. |
| Missing result for another student | Complete the registered course roster before approving/publishing. |
| Result is not linked to a registration | Correct its enrollment. Save an existing draft to attach a valid link. Escalate locked legacy-record reconciliation to the administrator. |
| Cannot remove a registration | Result history exists. Preserve the registration and use the relevant academic correction/outcome process. |
| Student cannot see marks | Confirm publication and the correct session/semester. Draft, submitted, reviewed and approved results remain hidden. |
| Resit button is missing | Open a published, graded, failed original result as admin/exam officer. Check whether a resit already exists. |
| Old grades did not change after policy update | Historical policy snapshots are preserved. Explicitly adopt a policy on a draft or request a policy correction. |
| Transcript issuance blocked | Resolve missing/unpublished course results and blocking exceptional outcomes. |
| Transcript is revoked | Obtain a newly issued document after the required correction/publication steps. |
| Attendance link expired | Ask staff to resend a current code; it lasts two minutes. |
| Forbidden or not found | Confirm account role, assignment and ownership. Ask admin to check access; repeated refreshes will not grant permission. |
| Email has not arrived | Check address and spam folder. Ask the technical administrator to check mail configuration and queued delivery. |

## 19. Current boundaries

This portal includes academic administration and supporting teaching tools. The current reviewed implementation does not establish a full finance/payroll, library, hostel, transport or parent-portal workflow. Do not treat role names such as Bursar as proof that those modules are implemented.

Other practical boundaries: department currently represents the programme for grading-policy purposes; repeat standing does not automate promotion; only one same-session resit is supported; completed admission information is not a separate offer-of-admission decision; online test scores do not automatically become published official course results; and graduation reports cover academic eligibility rather than complete institutional clearance.


## 20. Database backups and restoring

As admin, click **Backup & Restore** on the dashboard or sidebar. Use **Create backup now** to save a database copy. The list shows existing backups, their folders, dates and sizes. **Download** saves a private copy to your computer.

To restore, click **Review restore**, check the selected snapshot and tables with saved data, enter your current admin password, type the exact confirmation phrase, acknowledge replacement and click **Restore this backup**. This replaces current records, restores old account credentials and signs you out. The system first creates a safety backup and stops if it cannot do so. Restore during a quiet period with background writers paused by the server administrator.

If restoration fails after it starts changing tables, the portal stays in maintenance mode for server-level recovery. If you cannot log in because accounts were lost, or MySQL storage is damaged, this dashboard cannot bypass those problems.

Database backups do not include uploaded files or transcript PDFs. Read the full [Backup and restore guide](database-backups.md) before restoring.
