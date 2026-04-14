<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Courses;
use App\Models\ActivityLog;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasProfilePhoto, HasTeams, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'usertype', // Assuming your users table has a role column
        'matric_number',
        'department_id',
        'level',
    ];

    const ROLES = [
        'admin' => 'Admin',
        'student' => 'Student',
        'lecturer' => 'Lecturer',
        'exam_officer' => 'Exam Officer',
        'vc' => 'VC',
        'registrar' => 'Registrar',
        'bursar' => 'Bursar',
    ];

    public function isRole($role): bool
    {
        if (is_array($role)) {
            return in_array($this->role, $role, true);
        }
        return $this->role === $role;
    }

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
        ];
    }

    public function getRoleNameAttribute(): string
    {
        return self::ROLES[$this->usertype] ?? 'Unknown Role';
    }

    public function courseRegistrations()
    {
        return $this->hasMany(CourseRegistration::class);
    }

    public function completedCourses()
    {
        return $this->hasMany(CourseRegistration::class)
            ->where('status', 'completed'); // Ensure only completed courses are retrieved
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id'); // Foreign key 'department_id' in users table
    }

    public function classSchedules()
    {
        return $this->hasMany(ClassSchedule::class, 'lecturer', 'id');
    }

    public function assignedCourses()
    {
        return $this->belongsToMany(Courses::class, 'course_user', 'user_id', 'course_id')->withTimestamps();
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'actor_id');
    }
}
