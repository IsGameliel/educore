<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Courses;
use App\Models\ActivityLog;

class User extends Authenticatable implements \Illuminate\Contracts\Auth\MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasProfilePhoto, HasTeams, Notifiable, TwoFactorAuthenticatable, SoftDeletes;

    protected static function booted(): void
    {
        // Archived accounts remain available to audit queries, but cannot authenticate
        // or appear in normal student directories and account lookups.
        static::addGlobalScope('active_account', fn ($query) => $query->whereNull('users.merged_into_id'));
        static::deleting(function (User $user) {
            $user->tokens()->delete();
            $user->setRememberToken(null);
            $user->saveQuietly();
            \App\Support\ActivityLogger::log(auth()->user(), 'account_deactivated', 'Account deactivated; institutional records retained.', ['target_user' => $user]);
        });
    }

    public function delete()
    {
        return DB::transaction(function () {
            $current = static::withTrashed()->whereKey($this->getKey())->lockForUpdate()->first();
            if (! $current || $current->trashed()) {
                return false;
            }
            return parent::delete();
        }, 3);
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'usertype', // Assuming your users table has a role column
        'matric_number',
        'department_id',
        'level',
        'entry_year',
    ];

    const ROLES = [
        'admin' => 'Admin',
        'student' => 'Student',
        'lecturer' => 'Lecturer',
        'exam_officer' => 'Exam Officer',
        'vc' => 'VC',
        'registrar' => 'Registrar',
        'bursar' => 'Bursar',
        'dean' => 'Dean',
        'hod' => 'HOD',
        'librarian' => 'Librarian',
        'admission_officer' => 'Admission Officer',
        'accountant' => 'Accountant',
    ];

    public function dashboardRole(): string
    {
        $role = str_replace([' ', '-'], '_', strtolower(trim((string) $this->usertype)));

        return match ($role) {
            'lectuer' => 'lecturer',
            'liberian' => 'librarian',
            'burser' => 'bursar',
            'admissions_officer' => 'admission_officer',
            default => $role,
        };
    }

    public function isStaff(): bool
    {
        return in_array($this->dashboardRole(), [
            'admin', 'lecturer', 'dean', 'hod', 'librarian', 'admission_officer',
            'accountant', 'bursar', 'exam_officer', 'vc', 'registrar',
        ], true);
    }

    public function isAdmissionApplicant(): bool
    {
        return in_array($this->dashboardRole(), ['user', 'applicant', 'guest', ''], true);
    }

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
        'email_otp_hash',
        'email_otp_address',
        'email_otp_expires_at',
        'email_otp_sent_at',
        'email_otp_attempts',
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
            'temporary_id_issued_at' => 'date',
            'temporary_id_expires_at' => 'date',
            'email_otp_expires_at' => 'datetime',
            'email_otp_sent_at' => 'datetime',
            'email_otp_attempts' => 'integer',
            'password' => 'hashed',
            'role' => 'string',
        ];
    }

    public function sendEmailVerificationNotification()
    {
        try {
            app(\App\Services\EmailVerificationOtpService::class)->send($this);
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $exception) {
            report($exception);
            session()->flash('otp_error', 'We could not send your verification code. Please use Resend Code to try again.');
        }
    }

    public function getRoleNameAttribute(): string
    {
        return self::ROLES[$this->dashboardRole()] ?? 'Unknown Role';
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
        return $this->hasMany(ClassSchedule::class, 'lecturer_id', 'id');
    }

    public function assignedCourses()
    {
        return $this->belongsToMany(Courses::class, 'course_user', 'user_id', 'course_id')->withTimestamps();
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'actor_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id');
    }
}
