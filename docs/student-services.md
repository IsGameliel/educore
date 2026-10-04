# Student services

Students open **Student Services** from the sidebar or dashboard. Access is restricted to the signed-in student's own records.

## Onboarding

The checklist is calculated from verified email, enrollment fields, matriculation number, application payment where an application exists, active-session tuition clearance and registration activity in both semesters. Imported students without admission applications are not told to pay a new application fee. Registration started does not mean all required courses are registered. Missing enrollment or matriculation details direct the student to the academic office; tuition links go to existing invoices and clearance.

## Appeals and transcript updates

Appeal tracking explains payment pending, submitted/open, in review and closed states. Staff responses remain visible. Resolving an appeal does not itself change a grade.

Transcript tracking shows payment, review, issuance and rejection. Official document status and permitted downloads are displayed separately: revocation removes the download link even if the original request remains issued. Issued means available in the portal, not delivered to an outside recipient.

New request, status and response changes automatically enter the existing student dashboard/notification feed and a paginated service-update history. Official transcript revocations, including those caused by result corrections, are tracked. Events created inside a failed transaction roll back with it. Unchanged model saves do not add events. Tracking starts when this feature is enabled; existing records show their current status without fabricated historical transitions. These are portal notifications, not email notifications, and need no scheduler or queue worker. Direct database bulk updates bypass model observers and should not be used to decide student requests.

## Graduation preparation

The checklist reuses AcademicStanding and recorded grading policies. It shows earned credits, CGPA, missing required courses, outstanding failures, unresolved published results and registrations missing published results through the active session. Unpublished scores are never displayed. When graduation rules are absent, eligibility is explicitly unconfigured. Meeting recorded academic requirements is preparation for institutional review, not automatic graduation, financial clearance or certificate issuance.

No migration is needed: service events use the existing activity log. No existing requests, payments, academic decisions or student levels are changed by opening the page.
