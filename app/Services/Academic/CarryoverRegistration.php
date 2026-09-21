<?php

namespace App\Services\Academic;

use App\Models\Courses;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Collection;

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

        return Courses::where('department_id', $student->department_id)->forAcademicSession($session)
            ->whereIn('code', $outstanding->keys())->with('prerequisites')->orderBy('code')->get()
            ->filter(fn ($course) => $course->semester === $outstanding[$course->code]->semester)
            ->map(function ($course) use ($outstanding) {
                $course->setRelation('failedResult', $outstanding[$course->code]);
                return $course;
            });
    }
}
