For the full role-by-role tutorial, see [Portal user manual](portal-user-manual.md).

# Academic result management

Open **Academic Records** in the sidebar (`/academic`). Admin and exam-officer accounts can manage approvals, grading policies, appeals, official transcripts, and reports. Lecturers can enter and submit their assigned course results. Students can see their own published results, request transcripts, and submit appeals with private evidence.

## Institutional rules

- Workflow: draft → submitted → reviewed → approved → published. Admin may submit, review and approve their own results. For non-admin staff, review and approval must be performed by an authorized admin/exam officer other than the uploader or submitter. Lecturers cannot review or approve. Administrators and exam officers may publish approved results.
- Submitted records cannot be edited. Admin/exam officers can return submitted/reviewed results to draft with a reason. Approved/published marks require a correction request and a different admin/exam officer's approval. A correction creates a new version, withdraws the result until explicitly republished, and revokes affected documents.
- Ten or more **outstanding failed course codes in the selected academic session** means **Repeat**; one to nine means **Carryover**. A subsequently passed course is no longer an outstanding failure. Exceptional outcomes are unresolved outcomes, not invented zero scores. This is an academic standing flag; the software does not automatically change the student's level or enrolment.
- CGPA defaults to all graded attempts. Policies can instead use the latest or highest graded attempt. Earlier semester views exclude later semester results. Regular/repeat attempts and one separate resit per course/session/semester are supported; a subsequent session is another attempt. Spreadsheet imports update the primary attempt, not resits.
- The existing data model identifies programmes by department. Grading policies are versioned per department/session. Separate programmes within a department would require a distinct programme entity before assigning different policies to them.
- Policies include pass mark, CA/exam maximums totalling 100, grade bands on a 0–5 point scale, repeat calculation rule, and graduation requirements. Passing scores below the lowest positive grade band's minimum receive that lowest passing grade. Scores below the policy pass mark fail regardless of band.
- Updating the old department pass-mark page changes the fallback for future policies/results. Existing snapshots stay unchanged. To regrade existing marks, create a policy version and explicitly adopt it on a draft, or propose it in a correction to a locked result. Publication recalculates the student's displayed standing.
- Approval/publication checks the course registration roster, missing marks, unresolved incomplete/not-submitted records, and duplicate primary/resit records. Withdrawn, deferred, absent, and withheld outcomes remain explicit, without numeric grades. A withheld result cannot be included in an issued transcript.
- Graduation reporting is an academic eligibility check using unique passed credits, minimum CGPA, required course codes, outstanding failures, unresolved outcomes, and missing/unpublished registered-course results. It does not represent financial or administrative clearance. Without configured graduation requirements it reports “Requirements not configured”.

## Authorizing a resit and entering the new score

1. As admin or exam officer, open **Academic Records** and open the student's published failed original result.
2. Select **Authorize resit**, enter the reason, and submit. A separate resit draft is created with the original result linked, the authorizing officer, and authorization date recorded.
3. Enter the new CA/exam marks or a total score in the resit draft. Assigned lecturers may enter and submit the resit marks; only admin/exam officers authorize resits and review/approve/publish results.
4. Submit the resit, then review, approve and publish it. Non-admin uploaders/submitters require another authorized officer for review and approval; admin may complete these stages themselves.
5. Open **Exam attempts** to compare the original and resit scores. Students see the resit only after publication. The original score is never overwritten by entering a resit.

Passing the resit clears that course from outstanding failures. CGPA still follows the configured all/latest/highest attempt rule. Resits are authorized individually for students asked to retake the exam; they are not automatically created for every failure. One separate resit is supported per course/session/semester.

Existing installations that already ran the academic workflow migration also need:

```sh
php artisan migrate --path=database/migrations/2026_09_21_120000_link_resits_to_original_results.php
```

## Course registration and results

Results now carry a direct link to the student's course registration. Manual entry and Excel uploads require exactly one active (`registered`, `approved`, or `completed`) registration for the selected course, academic session and semester. Pending, rejected and withdrawn registrations cannot receive marks. The course's level is used even if the student has progressed to a higher level.

- **Admin → Course Registrations → select a student:** choose the session/semester. The Results column offers **Enter result** for active registrations without results, or links to existing exam and resit attempts with their workflow status.
- **Academic Records → New result:** select the course/session/semester; the student list contains only eligible registered students. Saving creates a draft and the usual review/publication process still applies.
- **Result upload → Download template:** each course sheet contains its registered students with blank marks. Fill in the marks and upload. Unregistered candidates are rejected with row errors. Shared courses require the matching course record and registration in each student's department.
- **Student → Registered Courses:** only published results appear alongside courses. Unpublished attempts and marks remain private.
- A registration with result history cannot be deleted, withdrawn, rejected, or moved to a different course/session/student. Deleted drafts also retain their registration history. Use result corrections for mark changes; use explicit result outcomes for exceptional cases.
- Resits share the original exam registration and remain separate score records. They do not require a second course registration.

Run `php artisan migrate --path=database/migrations/2026_09_21_130000_link_results_to_course_registrations.php` on existing installations. The migration links historical records only where the student, course, department, session and semester identify exactly one registration. It does not change marks, grades, versions or publication. Unmatched or ambiguous records stay intact; completeness checks flag them before approval. Correct the registration and save an existing draft to attach its link. Locked records continue to use the existing controlled workflow.

To test manually:

1. Register a student for a course, then open that student's course registrations and select **Enter result**. Save marks and confirm a draft link appears.
2. Try entering/uploading marks for an unregistered student or a different semester: the portal should reject them.
3. Log in as the student before publication: the registered course should say **No published result**. Submit, review, approve and publish using the required staff accounts; the published score should then appear.
4. Try removing the registration or changing its status to withdrawn after result entry: the portal should block the change.
5. For a published failure, authorize a resit and enter the new marks. The registration page should retain both attempts; the student sees the resit after publication.

## Documents and appeals

Semester downloads are **unofficial student copies**. Official transcripts require a tracked request and admin/exam-officer issuance. Official issuance is blocked while any results in scope are unpublished or registered courses lack results. PDFs live on Laravel's private local disk. Every download checks ownership/role, document status, and source result versions.

Official PDFs carry a random verification reference, document version, issue date, and verification URL. Public verification exposes status and PDF SHA-256 only, not names or grades. Revocation remains visible. This is verification against the portal's registry, not a cryptographic PDF signature.

Students can appeal missing results even when no result record exists. Evidence is limited to PDF/JPG/PNG, 5 MB, stored privately, and downloaded only by the submitting student or an admin/exam officer. Resolving an appeal does not bypass the result correction workflow.

## Upgrade an existing installation

Back up both MySQL and `storage/app` before upgrading. Deploy during a maintenance window so old public transcripts are not served between code deployment and archival.

```sh
php artisan down
php artisan backup:database
php artisan migrate --path=database/migrations/2026_09_20_120000_create_academic_result_workflow.php
php artisan migrate --path=database/migrations/2026_09_21_120000_link_resits_to_original_results.php
php artisan migrate --path=database/migrations/2026_09_21_130000_link_results_to_course_registrations.php
php artisan academic:prepare --dry-run
php artisan academic:prepare
php artisan optimize:clear
php artisan up
```

The migration intentionally places existing results in **draft** state. `academic:prepare` preserves their stored scores/grades, adds an explicitly labelled baseline policy snapshot and history entry, archives old public transcript PDFs under `storage/app/private/legacy-transcripts`, verifies each copy before removing the public original, and clears obsolete document links. It is safe to rerun. Existing grades are not retrospectively recalculated. Review legacy policies and resolve completeness issues before publishing.

New private documents are issued only after publication. Old archived PDFs are retained for records, not exposed through the new download routes. If the hosting server/CDN cached old public PDFs, purge those cached URLs during deployment.

The upgrade has no automatic rollback for archived PDFs or published academic actions. Restore the pre-upgrade backup if rolling back the application and schema together. Do not expose the private storage directory through the web server.

## Validation

Run the relevant tests against an isolated database, never the application database:

```sh
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/AcademicWorkflowTest.php tests/Feature/ResultAuthorizationTest.php tests/Feature/AdminCourseRegistrationTest.php tests/Feature/CourseRegistrationTest.php tests/Unit/ResultsImportTest.php tests/Unit/StudentUpdateFeedTest.php
```

Tests cover permissions, publication stages, locks, correction history, policy changes, repeat standing, resit attempts, historical CGPA, batch rollback, completeness, private documents, verification, revocation, appeals, legacy archival, and staff screen rendering.

### Registering carryover courses in a later session

The student opens Course Registration, keeps their current level, chooses the semester, and selects failed earlier courses under **Outstanding / Carryover Courses** alongside their normal courses. They may also submit only carryover courses.

An admin must first create the course offering in the current academic session with the same department, course code and semester. Only published, graded failures from earlier sessions qualify; a published pass clears the course from the outstanding list. Unpublished marks cannot establish eligibility. Current and carryover credits share the semester credit limit.

The new registration stores a reference to the previous failed result. Staff enter marks against the new session registration using the existing result entry/upload workflow. The new result is a separate repeat attempt and its detail page links to the previous failure; original scores remain unchanged. Approval and publication rules still apply. This differs from **Authorize resit** on an original result, which creates an additional exam attempt within that original session.
