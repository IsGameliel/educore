<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseMaterial extends Model
{
    protected $fillable = [
        'title',
        'level',
        'semester',
        'department_id',
        'course_id',
        'file_path',
        'cover_photo',
    ];

    public function course()
    {
        return $this->belongsTo(Courses::class, 'course_id');
    }

    /**
     * Get the department associated with the course material.
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
