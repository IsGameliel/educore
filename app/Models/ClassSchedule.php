<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use app\Models\User;
use app\Models\Courses;


class ClassSchedule extends Model
{
    // Specify the table name if it's not following Laravel's default naming convention
    protected $table = 'class_schedules';

    // Allowable fields for mass assignment
    protected $fillable = [
        'department_id',
        'level',
        'semester',
        'subject',
        'lecturer_id',
        'day',
        'start_time',
        'end_time',
        'room',
    ];

    // If you are using timestamps (created_at, updated_at), set this to true (default)
    public $timestamps = true;

    // If your table does not use timestamps, set this to false
    // public $timestamps = false;

    // If you have custom date formats (e.g., datetime), you can define them
    protected $dates = [
        'created_at',
        'updated_at',
        // Add any custom date fields if applicable
    ];

    // ClassSchedule Model
    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id', 'id') // Correct foreign key and related field
            ->where('usertype', 'lecturer');  // Filter users with 'lecturer' usertype
    }

}
