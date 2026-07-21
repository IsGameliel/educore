<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    public const STATUSES = ['present', 'late', 'absent', 'excused'];

    protected $fillable = [
        'attendance_session_id',
        'student_id',
        'status',
        'reason',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }

    public function attendanceSession()
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
