<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseRegistration extends Model
{
    // Define the table name (optional)
    protected $table = 'course_registrations';

    // Allow mass assignment for these attributes
    protected $fillable = [
        'user_id',
        'acted_by',
        'previous_result_id',
        'course_id',
        'status',
        'semester',
        'session',
        'registration_date',
    ];

    // Cast attributes to specific data types
    protected $casts = [
        'registration_date' => 'datetime',
    ];

    public function previousResult()
    {
        return $this->belongsTo(Result::class, 'previous_result_id');
    }

    public function results()
    {
        return $this->hasMany(Result::class, 'course_registration_id')->orderBy('attempt_number')->orderBy('id');
    }

    public function assertCanRemove(): void
    {
        if ($this->results()->withoutGlobalScopes()->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'course_registration' => 'This registration has result history and cannot be removed or withdrawn.',
            ]);
        }
    }

    public function save(array $options = [])
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($options) {
            User::whereKey($this->user_id)->lockForUpdate()->first();
            if ($this->exists && ($this->isDirty(['user_id', 'course_id', 'session', 'semester'])
                || ($this->isDirty('status') && ! in_array($this->status, \App\Services\Academic\ResultRegistration::ELIGIBLE_STATUSES, true)))) {
                $this->assertCanRemove();
            }
            return parent::save($options);
        });
    }

    public function delete()
    {
        return \Illuminate\Support\Facades\DB::transaction(function () {
            User::whereKey($this->user_id)->lockForUpdate()->first();
            $this->assertCanRemove();
            return parent::delete();
        });
    }

    // Define relationship with Courses
    public function course()
    {
        return $this->belongsTo(Courses::class);
    }

    // Define relationship with Users (students)
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    // Check if the registration is for a specific semester
    public function isForSemester(string $semester): bool
    {
        return $this->semester === $semester;
    }

    public static function getTotalCreditUnitsForSemester($userId, $semester, $session = null)
    {
        return self::where('user_id', $userId)
            ->where('course_registrations.semester', $semester)
            ->when($session, fn ($query) => $query->where('course_registrations.session', $session))
            ->join('courses', 'course_registrations.course_id', '=', 'courses.id')
            ->sum('courses.credit_unit');
    }
}
