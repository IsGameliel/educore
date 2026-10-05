<?php

namespace App\Services\Academic;

use App\Models\{CourseRegistration, Courses, User};
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrationGroupUpdate
{
    public function records(User $student, ?string $session, string $semester, bool $lock = false)
    {
        $query = CourseRegistration::where('user_id', $student->id)->where('semester', $semester)
            ->historySession($session ?: 'unassigned')->orderBy('id');
        return ($lock ? $query->lockForUpdate() : $query)->get();
    }

    public function fingerprint($records): string
    {
        return hash_hmac('sha256', json_encode($records->map(fn ($row) => $row->getAttributes())->all()), config('app.key'));
    }

    public function update(User $student, ?string $sourceSession, string $sourceSemester, ?string $session, string $semester,
        User $actor, string $reason, string $fingerprint, ?int $selectedId = null, array $selectedChanges = []): int
    {
        abort_unless($actor->dashboardRole() === 'admin' && $student->dashboardRole() === 'student', 403);
        return DB::transaction(function () use ($student, $sourceSession, $sourceSemester, $session, $semester, $actor, $reason, $fingerprint, $selectedId, $selectedChanges) {
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $records = $this->records($student, $sourceSession, $sourceSemester, true);
            if ($records->isEmpty() || !hash_equals($this->fingerprint($records), $fingerprint)) {
                throw ValidationException::withMessages(['registration' => 'This course group changed. Reload the editor before saving.']);
            }
            $before = $records->map(fn ($row) => $row->getAttributes())->all();
            $seen = [];
            foreach ($records as $record) {
                $course = Courses::with(['academicSession', 'prerequisites'])->findOrFail(
                    $record->id === $selectedId ? ($selectedChanges['course_id'] ?? $record->course_id) : $record->course_id);
                if ($course->semester !== $semester || ($course->academic_session_id && $course->academicSession?->name !== $session)) {
                    $matches = Courses::where('code', $course->code)->where('department_id', $course->department_id)
                        ->where('semester', $semester)->forAcademicSession($session ?? '')->with('prerequisites')->get();
                    if ($matches->count() !== 1) {
                        throw ValidationException::withMessages(['course_id' => 'Choose or create one matching offering for '.$course->code.' in the destination session and semester. No registrations were changed.']);
                    }
                    $course = $matches->first();
                }
                if ($record->id === $selectedId && $record->course_id != $course->id
                    && (string) $course->department_id !== (string) $student->department_id) {
                    throw ValidationException::withMessages(['course_id' => 'Choose a course from the student’s department.']);
                }
                $record->fill(['course_id' => $course->id, 'session' => $session, 'semester' => $semester]);
                if ($record->id === $selectedId && isset($selectedChanges['status'])) {
                    $record->status = $selectedChanges['status'];
                }
                if ($record->isDirty(['course_id', 'session', 'semester'])) {
                    if ($record->previous_result_id) {
                        throw ValidationException::withMessages(['course_id' => 'Registration #'.$record->id.' has a carryover link. Use the carryover workflow. No registrations were changed.']);
                    }
                    $record->assertCanRemove();
                } elseif ($record->isDirty('status') && !in_array($record->status, ResultRegistration::ELIGIBLE_STATUSES, true)) {
                    $record->assertCanRemove();
                }
                if (isset($seen[$course->id]) || CourseRegistration::where('user_id', $student->id)->whereNotIn('id', $records->modelKeys())
                    ->where('session', $session)->where('semester', $semester)->where('course_id', $course->id)->exists()) {
                    throw ValidationException::withMessages(['course_id' => 'The destination already contains '.$course->code.' or the group includes duplicate registrations. Resolve duplicates first.']);
                }
                $seen[$course->id] = true;
                if ($record->isDirty('course_id') && !in_array($record->status, ['rejected', 'withdrawn'], true)) {
                    foreach ($course->prerequisites as $prerequisite) {
                        if (!CourseRegistration::where('user_id', $student->id)->where('course_id', $prerequisite->id)
                            ->whereIn('status', ResultRegistration::ELIGIBLE_STATUSES)->exists()) {
                            throw ValidationException::withMessages(['course_id' => 'Missing prerequisite: '.$prerequisite->code]);
                        }
                    }
                }
                $record->setRelation('course', $course);
            }
            $others = CourseRegistration::where('user_id', $student->id)->whereNotIn('id', $records->modelKeys())
                ->where('session', $session)->where('semester', $semester)->whereNotIn('status', ['rejected', 'withdrawn'])->with('course')->get();
            $active = $records->reject(fn ($row) => in_array($row->status, ['rejected', 'withdrawn'], true));
            if ($active->isNotEmpty() && filled($session)) {
                $total = $others->merge($active)->sum(fn ($row) => $row->course?->credit_unit ?? 0);
                if ($total > StudentCreditLimit::for($student, $session, $semester)) {
                    throw ValidationException::withMessages(['course_id' => 'The destination credit limit is exceeded. Adjust the student’s credit load first.']);
                }
                $conflicts = TimetableConflicts::forCourses($others->merge($active)->pluck('course_id')->unique()->all());
                if ($conflicts) {
                    throw ValidationException::withMessages(['registration' => implode(' ', $conflicts)]);
                }
                app(\App\Services\TuitionBilling::class)->assertCleared($student, $session, $semester);
            }
            foreach ($records as $record) {
                $record->acted_by = $actor->id;
                $record->save();
            }
            ActivityLogger::log($actor, 'registration_group_corrected', 'Updated session/semester for '.$records->count().' courses for '.$student->name, [
                'target_user' => $student, 'department_id' => $student->department_id,
                'properties' => ['before' => $before, 'after' => $records->map(fn ($row) => $row->getAttributes())->all(), 'reason' => $reason],
            ]);
            return $records->count();
        }, 3);
    }
}
