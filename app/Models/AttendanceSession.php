<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    protected $fillable = [
        'class_schedule_id',
        'attendance_date',
        'taken_by',
        'scan_token',
        'scan_expires_at',
        'scan_code_sent_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'scan_expires_at' => 'datetime',
            'scan_code_sent_at' => 'datetime',
        ];
    }

    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    public function records()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function takenBy()
    {
        return $this->belongsTo(User::class, 'taken_by');
    }
}
