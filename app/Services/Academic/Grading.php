<?php

namespace App\Services\Academic;

use App\Models\Department;
use App\Models\GradingPolicy;
use App\Models\Result;
use Illuminate\Validation\ValidationException;

class Grading
{
    public static function policy(int $departmentId, string $session): array
    {
        $policy = GradingPolicy::where('department_id', $departmentId)->where('session', $session)->orderByDesc('version')->first();

        return $policy?->snapshot() ?? GradingPolicy::defaults(Department::find($departmentId)?->pass_mark);
    }

    public static function calculate(array $values, array $policy): array
    {
        $outcome = $values['outcome_status'] ?? 'graded';
        if (! in_array($outcome, ['graded', 'absent', 'incomplete', 'withheld', 'deferred', 'withdrawn', 'not_submitted'], true)) {
            throw ValidationException::withMessages(['outcome_status' => 'Select a valid result outcome.']);
        }
        if ($outcome !== 'graded') {
            return ['score' => null, 'ca_score' => null, 'exam_score' => null, 'grade' => null, 'grade_point' => null];
        }
        $ca = $values['ca_score'] ?? null;
        $exam = $values['exam_score'] ?? null;
        $total = $values['score'] ?? null;
        foreach (['ca_score' => [$ca, $policy['ca_max']], 'exam_score' => [$exam, $policy['exam_max']], 'score' => [$total, 100]] as $field => [$value, $max]) {
            if ($value !== null && $value !== '' && (! is_numeric($value) || ! is_finite((float) $value) || $value < 0 || $value > $max)) {
                throw ValidationException::withMessages([$field => "The value must be between 0 and {$max}."]);
            }
        }
        if (($ca !== null && $ca !== '') xor ($exam !== null && $exam !== '')) {
            throw ValidationException::withMessages(['score' => 'Enter both CA and exam marks, a total only, or select an exceptional outcome.']);
        }
        $score = Result::resolveScore($total, $ca, $exam);
        if ($score === null || $score > 100) {
            throw ValidationException::withMessages(['score' => 'A valid total score is required.']);
        }
        $grade = ['grade' => 'F', 'grade_point' => 0];
        if ($score >= $policy['pass_mark']) {
            $passingBands = collect($policy['bands'])->filter(fn ($band) => $band['point'] > 0)->sortByDesc('min');
            $lowestPass = $passingBands->last();
            if ($lowestPass) {
                $grade = ['grade' => $lowestPass['grade'], 'grade_point' => $lowestPass['point']];
            }
            foreach ($passingBands as $band) {
                if ($score >= $band['min']) {
                    $grade = ['grade' => $band['grade'], 'grade_point' => $band['point']];
                    break;
                }
            }
        }

        return $grade + ['score' => $score, 'ca_score' => $ca === '' ? null : $ca, 'exam_score' => $exam === '' ? null : $exam];
    }
}
