<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    // Define the fillable attributes for mass assignment
    protected $fillable = [
        'name', // Add the department's name or any other fields you want to mass-assign
        'code', // If your department has a code or abbreviation, add it here
        'faculty_id', // If departments are related to a faculty, include this
    ];

    // Define the relationship with the Courses model (one department has many courses)
    public function courses()
    {
        return $this->hasMany(Courses::class); // A department has many courses
    }

    // Define the relationship with the User model (a department has many users)
    public function users()
    {
        return $this->hasMany(User::class); // A department has many users
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id'); // foreign key 'faculty_id' in departments table
    }

    public function classSchedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }
    public function courseMaterials()
    {
        return $this->hasMany(CourseMaterial::class, 'department_id');
    }
}
