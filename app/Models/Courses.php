<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Courses extends Model
{
    protected $table = 'courses';

    // Define the fillable attributes for the course
    protected $fillable = [
        'code',
        'title',
        'credit_unit',
        'semester',
        'department_id',
        'level',
    ];

    // Define the relationship with the Department model (belongs to a department)
    public function department()
    {
        return $this->belongsTo(Department::class); // A course belongs to a department
    }

    // Define the relationship with CourseRegistration
    public function courseRegistrations()
    {
        return $this->hasMany(CourseRegistration::class);
    }

    /**
     * A course may have many prerequisites (many-to-many relationship).
     */
    public function prerequisites()
    {
        // The 'belongsToMany' method defines the relationship
        return $this->belongsToMany(Courses::class, 'course_prerequisites', 'course_id', 'prerequisite_id');
    }

    /**
     * A course may be a prerequisite for many other courses (inverse many-to-many).
     */
    public function coursesThatRequireIt()
    {
        return $this->belongsToMany(Courses::class, 'course_prerequisites', 'prerequisite_id', 'course_id');
    }

    /**
     * Check if the course has prerequisites.
     */
    public function hasPrerequisites(): bool
    {
        // Check if there are any prerequisites for the course
        return $this->prerequisites()->exists(); // Checks if prerequisites exist
    }

    /**
     * Check if the course is offered in a specific semester.
     */
    public function isOfferedInSemester(string $semester): bool
    {
        // Return true if the course is offered in the given semester
        return $this->semester === $semester;
    }

    public function courseMaterials()
    {
        return $this->hasMany(CourseMaterial::class, 'course_id');
    }

}
