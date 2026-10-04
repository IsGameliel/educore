# Academic assistance

Open **Academic Assistance** in the student, lecturer, exam officer or admin sidebar. Dashboard links lead to the same page.

## Registration

Students see a checklist on the registration page and Academic Assistance: registered units versus their individual semester limit, tuition clearance, available unregistered courses, carryovers and missing prerequisite registrations. It follows the portal's existing prerequisite-registration rule; it does not introduce a passed-result requirement. Enrollment problems direct students to the academic office. Suggestions never submit registration automatically.

Student and admin course registration reject overlaps between selected courses; student submissions also check existing active registrations. Missing timetable entries cannot be checked. Existing conflicting registrations are flagged in the checklist, not automatically removed.

## Timetables

Creating or editing a class schedule checks overlapping times for the same room, lecturer or department/level in the course's academic session and semester. Adjacent classes are allowed. The form shows the conflicting course and time. The chosen course must match the department, level and semester, and the assigned lecturer must have a lecturer account. Session locks serialize schedule writes through these endpoints.

Existing schedules remain unchanged. Students' timetable and dashboard show their current-session cohort and registered carryover classes, instead of every level and historical session in their department.

## Result-review reminders

Dashboard counts and the assistance page show results unchanged for three days. Lecturers see drafts for assigned courses; admins and exam officers see submitted, reviewed and approved results awaiting their next stage. Up to 100 course/stage groups are shown, oldest first. Opening a result uses the existing access and approval rules. Students never see unpublished reminders.

These are live portal reminders, recalculated when the page is opened. They do not send email, change grades, approve results or publish automatically. The waiting clock uses the last result update, so editing or progressing a result resets it.

## Attendance

The default range is the past 30 days, adjustable with From/To dates. Each row summarizes one student and course, with present, late, absent, excused and attendance rate. Present and late count as attended; excused records are excluded from the denominator. An entirely excused set shows Not applicable.

Future meetings and open scan sessions are excluded. Summaries use recorded attendance only; missing records are not assumed absent. Students see only themselves; lecturers see only schedules assigned to them; admins and exam officers see all records. No exam-eligibility threshold or disciplinary action is imposed.

No database migration, background worker or new scheduled task is required for these live checks and summaries.
