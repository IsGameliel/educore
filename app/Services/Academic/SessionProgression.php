<?php

namespace App\Services\Academic;

use App\Models\{AcademicSession, Result, TuitionInvoice, User};
use App\Services\EnrollmentData;
use Illuminate\Support\Facades\DB;

class SessionProgression
{
    public function preview(AcademicSession $target): array
    {
        $source = AcademicSession::current();
        $maxCarryovers = \App\Models\PromotionPolicy::current()->max_carryovers;
        $rows = User::where('usertype', 'student')->with('department')->orderBy('id')->get()->map(function ($student) use ($source, $target, $maxCarryovers) {
            $issues = EnrollmentData::issues($student, $source);
            $next = (string) ((int) $student->level + 100);
            $standing = null;
            $carryovers = null;
            if (! $source || (int) $target->start_year !== (int) $source->start_year + 1) {
                $issues[] = 'Promotion requires consecutive sessions. Activation alone keeps current levels.';
            }
            if ((int) $student->level >= 500) { $issues[] = 'Final or extended level: review program duration and graduation separately.'; }
            if (DB::table('student_progressions')->where('user_id', $student->id)->where('to_session_id', $target->id)->exists()) {
                $issues[] = 'Already promoted into this session.';
            }
            if (TuitionInvoice::current()->where('user_id', $student->id)->where('academic_session_id', $target->id)->exists()) {
                $issues[] = 'Target session already has an invoice. Review enrollment and billing before changing level.';
            }
            if ($source && $student->department_id && $student->department) {
                $report = AcademicStanding::report($student, $student->department_id, $source->name);
                $standing = $report['eligible'] ? 'Graduation candidate' : $report['standing'];
                if ($report['eligible']) { $issues[] = 'Graduation candidate: review graduation instead of promoting.'; }
                $carryovers = $report['outstanding']->count();
                if ($carryovers > $maxCarryovers) { $issues[] = "{$carryovers} carryovers exceed the promotion limit of {$maxCarryovers}."; }
                if ($report['missingResults']->isNotEmpty() || $report['unresolved']->isNotEmpty()) { $issues[] = 'Missing or unresolved results.'; }
                $current = $report['results']->where('session', $source->name);
                foreach (['First', 'Second'] as $semester) {
                    if ($current->where('semester', $semester)->isEmpty()) { $issues[] = 'No published '.$semester.' semester results.'; }
                }
                if (Result::where('user_id', $student->id)->where('department_id', $student->department_id)
                    ->where('session', $source->name)->where('workflow_status', '!=', 'published')->exists()) {
                    $issues[] = 'Unpublished results require review.';
                }
            }
            return ['student' => $student, 'next' => $next, 'standing' => $standing, 'carryovers' => $carryovers, 'issues' => $issues, 'eligible' => ! $issues];
        });
        $signature = $rows->map(fn ($row) => [$row['student']->id, $row['student']->level, $row['student']->department_id,
            $row['student']->entry_year, $row['student']->updated_at?->toISOString(), $row['next'], $row['carryovers'], $row['issues']])->all();
        $token = hash_hmac('sha256', json_encode([$source?->id, $source?->name, $target->id, $target->name, $maxCarryovers, $signature]), config('app.key'));
        return compact('source', 'target', 'rows', 'token', 'maxCarryovers');
    }
}
