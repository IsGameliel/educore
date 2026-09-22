<?php

namespace App\Models;

use App\Services\Academic\Grading;
use App\Services\Academic\ResultWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Result extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'outcome_status', 'attempt_type', 'course_registration_id',
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

    protected $casts = ['policy_snapshot' => 'array', 'published_at' => 'datetime', 'resit_authorized_at' => 'datetime'];

    protected ?array $auditBefore = null;

    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            // Serialize writes for a student, including imports and concurrent edits.
            User::whereKey($this->user_id)->lockForUpdate()->first();
            if ($this->exists) {
                $current = static::withoutGlobalScopes()->whereKey($this->id)->lockForUpdate()->first();
                if ($current && ($current->version != $this->getOriginal('version') || $current->workflow_status !== $this->getOriginal('workflow_status'))) {
                    throw ValidationException::withMessages(['result' => 'This result changed. Reload before saving.']);
                }
            }

            if ($this->exists && $this->getOriginal('course_registration_id') && $this->isDirty('course_registration_id')) {
                throw ValidationException::withMessages(['course_registration' => 'A result must retain its original course registration.']);
            }
            if ($this->course_registration_id) {
                \App\Services\Academic\ResultRegistration::validate($this);
            }
            return parent::save($options);
        });
    }

    public function delete()
    {
        return DB::transaction(function () {
            User::whereKey($this->user_id)->lockForUpdate()->first();
            $current = static::withoutGlobalScopes()->whereKey($this->id)->lockForUpdate()->first();
            if ($current && $current->workflow_status !== 'draft') {
                throw ValidationException::withMessages(['result' => 'Only draft results may be deleted.']);
            }

            return parent::delete();
        });
    }

    protected static function booted(): void
    {
        // Student queries throughout the portal must never expose unpublished marks.
        static::addGlobalScope('student_visibility', function ($query) {
            if (auth()->user()?->dashboardRole() === 'student') {
                $query->where('results.user_id', auth()->id())->where('results.workflow_status', 'published');
            }
        });
        static::saving(function (Result $result) {
            $existing = $result->exists;
            $result->auditBefore = $existing ? $result->getOriginal() : null;
            if ($existing && $result->isDirty('attempt_type')) {
                throw ValidationException::withMessages(['attempt_type' => 'An existing attempt cannot be relabelled. Authorize a separate resit from the original result.']);
            }
            if ($existing && $result->getOriginal('resit_of_result_id') && $result->isDirty(['user_id', 'department_id', 'course_code', 'session', 'semester', 'resit_of_result_id'])) {
                throw ValidationException::withMessages(['result' => 'A resit must remain linked to its original student, course and semester.']);
            }

            if ($existing && $result->getOriginal('workflow_status') !== 'draft') {
                throw ValidationException::withMessages(['result' => 'Submitted results are locked. Return to draft or request a correction.']);
            }
            if (! $existing) {
                $result->workflow_status = 'draft';
                $result->version = 1;
            }
            $result->outcome_status ??= 'graded';
            $result->attempt_type ??= 'regular';
            if (! $result->policy_snapshot || $result->isDirty(['session', 'department_id'])) {
                $result->policy_snapshot = Grading::policy($result->department_id, $result->session);
                $result->grading_policy_id = $result->policy_snapshot['id'] ?? null;
            }
            $result->forceFill(Grading::calculate($result->getAttributes(), $result->policy_snapshot));
            $duplicate = static::withoutGlobalScopes()->whereNull('deleted_at')->where('user_id', $result->user_id)
                ->where('department_id', $result->department_id)->where('session', $result->session)
                ->where('semester', $result->semester)->where('course_code', $result->course_code)
                ->whereIn('attempt_type', $result->attempt_type === 'resit' ? ['resit'] : ['regular', 'repeat'])
                ->when($existing, fn ($q) => $q->where('id', '!=', $result->id))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['result' => 'A result already exists for this student, course and semester. Edit the existing draft or request a correction.']);
            }
            $result->attempt_number = 1 + static::withoutGlobalScopes()->whereNull('deleted_at')
                ->where('user_id', $result->user_id)->where('department_id', $result->department_id)
                ->where('course_code', $result->course_code)
                ->where(fn ($q) => $q->where('session', '<', $result->session)->orWhere(fn ($q) => $q->where('session', $result->session)->where('semester', $result->attempt_type === 'resit' ? '<=' : '<', $result->semester)))
                ->when($existing, fn ($q) => $q->where('id', '!=', $result->id))->count();
            if ($result->attempt_number > 1 && $result->attempt_type === 'regular') {
                $result->attempt_type = 'repeat';
            }
            if ($existing && $result->isDirty()) {
                $result->version = ($result->getOriginal('version') ?? 1) + 1;
            }
        });
        static::saved(function (Result $result) {
            ResultWorkflow::record($result,
                $result->auditBefore,
                $result->auditBefore === null ? 'created' : 'draft_updated',
                request()->input('reason', $result->auditBefore === null ? 'Initial result entry' : 'Draft result updated'));
        });
        static::deleting(function (Result $result) {
            if ($result->workflow_status !== 'draft') {
                throw ValidationException::withMessages(['result' => 'Only draft results may be deleted.']);
            }
            ResultWorkflow::record($result, $result->attributesToArray(), 'deleted', 'Draft result deleted');
        });
    }

    public function registration()
    {
        return $this->belongsTo(CourseRegistration::class, 'course_registration_id');
    }

    public function resitOf()
    {
        return $this->belongsTo(self::class, 'resit_of_result_id');
    }

    public function resitAttempts()
    {
        return $this->hasMany(self::class, 'resit_of_result_id')->orderBy('attempt_number');
    }

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
     * @param  int|null  $passMark
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
        return $value === null ? null : number_format($value, 2);
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
