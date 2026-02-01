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
        'course_id',
        'semester',
        'registration_date',
    ];

    // Cast attributes to specific data types
    protected $casts = [
        'registration_date' => 'datetime',
    ];

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

    // Check if the registration is for a specific semester
    public function isForSemester(string $semester): bool
    {
        return $this->semester === $semester;
    }

    public static function getTotalCreditUnitsForSemester($userId, $semester)
    {
        return self::where('user_id', $userId)
            ->where('course_registrations.semester', $semester)  // Specify the table for 'semester'
            ->join('courses', 'course_registrations.course_id', '=', 'courses.id')
            ->sum('courses.credit_unit');
    }
}
