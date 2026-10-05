<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AcademicSession, CourseRegistration, Courses, User};
use App\Services\Academic\{ResultRegistration, StudentCreditLimit, TimetableConflicts};
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule, ValidationException};

class RegistrationEditController extends Controller
{
    public const STATUSES = ['pending', 'registered', 'approved', 'completed', 'rejected', 'withdrawn'];

    public function edit(User $student, CourseRegistration $registration, Request $request)
    {
        $this->checkStudent($student, $registration);
        $courses = Courses::with('academicSession')->where(fn ($q) => $q->where('department_id', $student->department_id)
            ->orWhere('id', $registration->course_id))->orderBy('code')->get();
        $sessions = $this->sessions($registration);
        $fingerprint = $this->fingerprint($registration);
        $hasResults = $registration->results()->withoutGlobalScopes()->exists();
        $groupService = app(\App\Services\Academic\RegistrationGroupUpdate::class);
        $groupRecords = $groupService->records($student, $registration->session, $registration->semester);
        $groupFingerprint = $groupService->fingerprint($groupRecords);
        $scope = $request->query('scope') === 'bulk' ? 'bulk' : 'single';
        return view('admin.course_registrations.edit-registration', compact('student', 'registration', 'courses', 'sessions', 'fingerprint', 'hasResults', 'groupRecords', 'groupFingerprint', 'scope'));
    }

    public function update(Request $request, User $student, CourseRegistration $registration)
    {
        $this->checkStudent($student, $registration);
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'session' => ['nullable', 'string', Rule::in($this->sessions($registration)->all())],
            'semester' => ['required', 'in:First,Second'], 'status' => ['required', Rule::in(self::STATUSES)],
            'fingerprint' => ['required', 'string', 'size:64'], 'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'group_fingerprint' => ['nullable', 'string', 'size:64'],
            'update_scope' => ['nullable', 'in:single,bulk'],
        ]);
        $data['session'] = $data['session'] ?? null;
        if (($data['update_scope'] ?? 'single') === 'bulk'
            && ($data['session'] !== $registration->session || $data['semester'] !== $registration->semester)) {
            if (!hash_equals($this->fingerprint($registration), $data['fingerprint'])) {
                throw ValidationException::withMessages(['registration' => 'This registration changed. Reload the editor.']);
            }
            $groupService = app(\App\Services\Academic\RegistrationGroupUpdate::class);
            $records = $groupService->records($student, $registration->session, $registration->semester);
            if ($records->count() > 1 && empty($data['group_fingerprint'])) {
                throw ValidationException::withMessages(['registration' => 'Reload the editor to review all courses affected by this session or semester change.']);
            }
            $count = $groupService->update($student, $registration->session, $registration->semester, $data['session'], $data['semester'],
                $request->user(), $data['reason'], $data['group_fingerprint'] ?? $groupService->fingerprint(collect([$registration])), $registration->id, $data);
            return redirect()->route('admin.course-registrations.show', ['student' => $student->id, 'semester' => $data['semester'], 'session' => $data['session'] ?: 'unassigned'])
                ->with('success', 'Updated session/semester for all '.$count.' courses in this registration group.');
        }
        DB::transaction(function () use ($request, $student, $registration, $data) {
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $current = CourseRegistration::whereKey($registration->id)->lockForUpdate()->firstOrFail();
            $this->checkStudent($student, $current);
            if (!hash_equals($this->fingerprint($current), $data['fingerprint'])) {
                throw ValidationException::withMessages(['registration' => 'This registration changed. Reload the editor before saving.']);
            }
            $before = $current->getAttributes();
            $current->fill(collect($data)->only(['course_id', 'session', 'semester', 'status'])->all());
            $identityChanged = $current->isDirty(['course_id', 'session', 'semester']);
            if ($identityChanged && $current->previous_result_id) {
                throw ValidationException::withMessages(['course_id' => 'This carryover registration is linked to a previous result. Correct it through the carryover workflow.']);
            }
            if ($identityChanged || $current->isDirty('status')) {
                if ($identityChanged || !in_array($current->status, ResultRegistration::ELIGIBLE_STATUSES, true)) {
                    $current->assertCanRemove();
                }
                $course = Courses::with(['academicSession', 'prerequisites'])->findOrFail($data['course_id']);
                if ($identityChanged) {
                    if (($current->isDirty('course_id') && (string) $course->department_id !== (string) $student->department_id)
                        || $course->semester !== $data['semester']
                        || ($course->academic_session_id && $course->academicSession?->name !== $data['session'])) {
                        throw ValidationException::withMessages(['course_id' => 'Choose a course offered in the selected session and semester for the student’s department.']);
                    }
                }
                if ($identityChanged && CourseRegistration::where('user_id', $student->id)->whereKeyNot($current->id)
                    ->where('course_id', $data['course_id'])->where('session', $data['session'])->where('semester', $data['semester'])->exists()) {
                    throw ValidationException::withMessages(['course_id' => 'This student already has this course registered for that session and semester.']);
                }
                $active = !in_array($data['status'], ['rejected', 'withdrawn'], true);
                if ($active && $current->isDirty('course_id')) {
                    foreach ($course->prerequisites as $prerequisite) {
                        if (!CourseRegistration::where('user_id', $student->id)->where('course_id', $prerequisite->id)
                            ->whereIn('status', ResultRegistration::ELIGIBLE_STATUSES)->exists()) {
                            throw ValidationException::withMessages(['course_id' => 'Missing prerequisite: '.$prerequisite->code]);
                        }
                    }
                }
                if ($active && filled($data['session'])) {
                    $others = CourseRegistration::where('user_id', $student->id)->whereKeyNot($current->id)
                        ->where('session', $data['session'])->where('semester', $data['semester'])
                        ->whereNotIn('status', ['rejected', 'withdrawn'])->with('course')->get();
                    $total = $others->sum(fn ($row) => $row->course?->credit_unit ?? 0) + $course->credit_unit;
                    $previousActive = !in_array($before['status'], ['rejected', 'withdrawn'], true);
                    if (($identityChanged || !$previousActive) && $total > StudentCreditLimit::for($student, $data['session'], $data['semester'])) {
                        throw ValidationException::withMessages(['course_id' => 'The change exceeds the student’s credit limit. Adjust the credit load first if required.']);
                    }
                    $conflicts = TimetableConflicts::forCourses($others->pluck('course_id')->push($course->id)->unique()->all());
                    if ($conflicts) {
                        throw ValidationException::withMessages(['course_id' => implode(' ', $conflicts)]);
                    }
                    if ($identityChanged || !$previousActive || ($before['status'] === 'pending' && in_array($data['status'], ResultRegistration::ELIGIBLE_STATUSES, true))) {
                        app(\App\Services\TuitionBilling::class)->assertCleared($student, $data['session'], $data['semester']);
                    }
                }
            }
            $current->acted_by = $request->user()->id;
            $current->save();
            ActivityLogger::log($request->user(), 'registration_corrected', 'Updated registration #'.$current->id.' for '.$student->name, [
                'subject' => $current, 'target_user' => $student, 'department_id' => $student->department_id,
                'properties' => ['before' => $before, 'after' => $current->getAttributes(), 'reason' => $data['reason']],
            ]);
        }, 3);
        return redirect()->route('admin.course-registrations.show', [
            'student' => $student->id, 'semester' => $data['semester'], 'session' => $data['session'] ?: 'unassigned',
        ])->with('success', 'Registration updated successfully.');
    }

    private function checkStudent(User $student, CourseRegistration $registration): void
    {
        abort_unless($student->dashboardRole() === 'student' && (int) $registration->user_id === (int) $student->id, 404);
    }

    private function sessions(CourseRegistration $registration)
    {
        return AcademicSession::orderByDesc('start_year')->pluck('name')->push($registration->session)->filter()->unique()->values();
    }

    private function fingerprint(CourseRegistration $registration): string
    {
        return hash_hmac('sha256', json_encode($registration->getAttributes()), config('app.key'));
    }
}
