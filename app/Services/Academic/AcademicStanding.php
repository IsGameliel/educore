<?php

namespace App\Services\Academic;

use App\Models\CourseRegistration;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Collection;

class AcademicStanding
{
    public const REPEAT_FAILURE_THRESHOLD = 10;

    public static function counted(Collection $results, string $rule = 'all'): Collection
    {
        $graded = $results->where('outcome_status', 'graded');
        if ($rule === 'all') {
            return $graded;
        }

        return $graded->groupBy('course_code')->map(fn ($attempts) => $rule === 'highest'
            ? $attempts->sortByDesc('grade_point')->first()
            : $attempts->sortBy(fn ($r) => $r->session.'-'.$r->semester.'-'.str_pad($r->attempt_number ?? 1, 4, '0', STR_PAD_LEFT))->last())->values();
    }

    public static function gpa(Collection $results, string $rule = 'all'): ?float
    {
        $counted = self::counted($results, $rule);
        $credits = $counted->sum('credit_unit');

        return $credits > 0 ? round($counted->sum(fn ($r) => $r->credit_unit * $r->grade_point) / $credits, 2) : null;
    }

    public static function report(User $student, int $departmentId, string $session, ?string $semester = null): array
    {
        $results = Result::where('user_id', $student->id)->where('department_id', $departmentId)
            ->where('workflow_status', 'published')->where('session', '<=', $session)
            ->when($semester === 'First', fn ($q) => $q->where(fn ($q) => $q->where('session', '<', $session)->orWhere('semester', 'First')))
            ->get();
        // Use the recorded policy of the latest applicable result for reproducible historical CGPA.
        $policy = $results->sortBy(fn ($r) => $r->session.'-'.$r->semester.'-'.str_pad($r->attempt_number ?? 1, 4, '0', STR_PAD_LEFT).'-'.str_pad($r->id, 12, '0', STR_PAD_LEFT))->last()?->policy_snapshot
            ?? Grading::policy($departmentId, $session);
        $passed = $results->filter(fn ($r) => $r->outcome_status === 'graded' && $r->grade_point > 0)->groupBy('course_code');
        $latest = $results->sortBy(fn ($r) => $r->session.'-'.$r->semester.'-'.str_pad($r->attempt_number ?? 1, 4, '0', STR_PAD_LEFT))->groupBy('course_code')->map->last();
        $outstanding = $latest->filter(fn ($r) => $r->outcome_status === 'graded' && (float) $r->grade_point === 0.0 && ! $passed->has($r->course_code));
        $failedThisSession = $outstanding->where('session', $session)->count();
        $earnedCredits = $passed->sum(fn ($attempts) => $attempts->max('credit_unit'));
        $cgpa = self::gpa($results, $policy['repeat_rule']);
        $missingRequired = collect($policy['required_courses'] ?? [])->diff($passed->keys())->values();
        $configured = ($policy['graduation_credits'] ?? null) !== null && ($policy['graduation_cgpa'] ?? null) !== null && ! empty($policy['required_courses']);
        $unresolved = $latest->whereIn('outcome_status', ['absent', 'incomplete', 'withheld', 'deferred', 'not_submitted']);
        $missingResults = CourseRegistration::with('course')->where('user_id', $student->id)
            ->whereIn('status', ['registered', 'approved', 'completed'])->where('session', '<=', $session)
            ->when($semester === 'First', fn ($q) => $q->where(fn ($q) => $q->where('session', '<', $session)->orWhere('semester', 'First')))
            ->whereHas('course', fn ($q) => $q->where('department_id', $departmentId))->get()
            ->filter(fn ($registration) => ! $results->contains(fn ($r) => $r->course_code === $registration->course->code && $r->session === $registration->session && $r->semester === $registration->semester));
        $eligible = $configured && $earnedCredits >= $policy['graduation_credits'] && $cgpa !== null && $cgpa >= $policy['graduation_cgpa'] && $missingRequired->isEmpty() && $outstanding->isEmpty() && $unresolved->isEmpty() && $missingResults->isEmpty();

        return compact('results', 'policy', 'cgpa', 'earnedCredits', 'outstanding', 'failedThisSession', 'missingRequired', 'configured', 'eligible', 'unresolved', 'missingResults') + [
            'standing' => $failedThisSession >= self::REPEAT_FAILURE_THRESHOLD ? 'Repeat' : ($failedThisSession ? 'Carryover' : 'No outstanding failures'),
        ];
    }
}
