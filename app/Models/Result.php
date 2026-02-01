<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Result extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'matric_number',
        'session',
        'semester',
        'level',
        'course_code',
        'course_title',
        'credit_unit',
        'score',
        'grade',
        'grade_point',
        'transcript_path',
        'department_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function calculateGradeAndPoint($score)
    {
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
