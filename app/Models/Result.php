<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Result extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'uploaded_by',
        'matric_number',
        'session',
        'semester',
        'level',
        'course_code',
        'course_title',
        'credit_unit',
        'ca_score',
        'exam_score',
        'score',
        'grade',
        'grade_point',
        'source_result_id',
        'transcript_path',
        'full_transcript_path',
        'department_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function sourceResult()
    {
        return $this->belongsTo(self::class, 'source_result_id');
    }

    // default pass mark used when a department has not yet been configured
    public const DEFAULT_PASS_MARK = 40;

    /**
     * Determine the letter grade and grade point for a given score.  A
     * department-specific pass mark can be supplied; any score below that
     * threshold will automatically be considered a failing grade (F).
     *
     * @param  float|int  $score
     * @param  int|null   $passMark
     * @return array{grade:string,grade_point:float}
     */
    public static function calculateGradeAndPoint($score, $passMark = null)
    {
        $pass = is_null($passMark) ? self::DEFAULT_PASS_MARK : $passMark;
        // ensure pass mark is a sensible integer 0–100
        $pass = max(0, min(100, intval($pass)));

        // failing is evaluated first so that a higher pass mark can override
        // the usual grade boundaries (e.g. a 45 with a 50 pass mark becomes F).
        if ($score < $pass) {
            return ['grade' => 'F', 'grade_point' => 0.0];
        }

        if ($score >= 70) {
            return ['grade' => 'A', 'grade_point' => 5.0];
        } elseif ($score >= 60) {
            return ['grade' => 'B', 'grade_point' => 4.0];
        } elseif ($score >= 50) {
            return ['grade' => 'C', 'grade_point' => 3.0];
        } elseif ($score >= 45) {
            return ['grade' => 'D', 'grade_point' => 2.0];
        } elseif ($score >= 40) {
            return ['grade' => 'E', 'grade_point' => 1.0];
        } else {
            return ['grade' => 'F', 'grade_point' => 0.0];
        }
    }

    public static function resolveScore($score = null, $caScore = null, $examScore = null)
    {
        $hasCa = $caScore !== null && $caScore !== '';
        $hasExam = $examScore !== null && $examScore !== '';

        if ($hasCa || $hasExam) {
            return round((float) ($caScore ?: 0) + (float) ($examScore ?: 0), 2);
        }

        return $score === null || $score === '' ? null : round((float) $score, 2);
    }

    public function getGradePointAttribute($value)
    {
        return number_format($value, 2);
    }

    public function scopeBySessionAndSemester($query, $session, $semester)
    {
        return $query->where('session', $session)->where('semester', $semester);
    }

    public function scopeByUserAndLevel($query, $userId, $level)
    {
        return $query->where('user_id', $userId)->where('level', $level);
    }
}
