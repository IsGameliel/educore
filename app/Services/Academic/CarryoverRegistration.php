<?php

namespace App\Services\Academic;

use App\Models\AcademicSession;
use App\Models\Courses;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CarryoverRegistration
{
    public static function available(User $student, string $session): Collection
    {
        $history = Result::where('user_id', $student->id)->where('department_id', $student->department_id)
            ->where('workflow_status', 'published')->where('outcome_status', 'graded')
            ->orderByDesc('id')->get()->groupBy('course_code');
        $outstanding = $history->filter(fn ($attempts) => ! $attempts->contains(fn ($r) => $r->grade_point !== null && $r->grade_point > 0))
            ->map(fn ($attempts) => $attempts->first(fn ($r) => $r->grade_point !== null && $r->grade_point == 0 && strcmp($r->session, $session) < 0))
            ->filter();

        // Create missing offerings on demand for every student's outstanding courses.
        // Serialize by session so students sharing a course reuse the same offering.
        if ($outstanding->isNotEmpty()) {
            DB::transaction(function () use ($student, $session, $outstanding) {
                $academicSession = AcademicSession::where('name', $session)->lockForUpdate()->first();
                if (! $academicSession) {
                    return;
                }
                foreach ($outstanding as $failure) {
                    $source = Courses::where('department_id', $student->department_id)
                        ->where('code', $failure->course_code)->where('semester', $failure->semester)
                        ->forAcademicSession($failure->session)->with('prerequisites')->first();
                    $offering = Courses::firstOrCreate([
                        'department_id' => $student->department_id,
                        'academic_session_id' => $academicSession->id,
                        'code' => $failure->course_code,
                        'semester' => $failure->semester,
                    ], [
                        'title' => $source?->title ?? $failure->course_title,
                        'credit_unit' => $source?->credit_unit ?? $failure->credit_unit,
                        'level' => $source?->level ?? $failure->level,
                    ]);
                    if ($offering->wasRecentlyCreated && $source) {
                        $offering->prerequisites()->sync($source->prerequisites->modelKeys());
                    }
                }
            });
        }

        return Courses::where('department_id', $student->department_id)->forAcademicSession($session)
            ->whereIn('code', $outstanding->keys())->with('prerequisites')->orderBy('code')->get()
            ->filter(fn ($course) => $course->semester === $outstanding[$course->code]->semester)
            ->map(function ($course) use ($outstanding) {
                $course->setRelation('failedResult', $outstanding[$course->code]);
                return $course;
            });
    }
}
