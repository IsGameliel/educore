<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    // Define the fillable attributes for mass assignment
    protected $fillable = [
        'name',         // The name of the faculty
        'description',  // A description for the faculty (optional)
        'code',         // The unique code for the faculty
        // Add any other fields that should be mass assignable
    ];

    /**
     * Define the relationship with the Department model (one faculty has many departments).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function departments()
    {
        return $this->hasMany(Department::class, 'faculty_id'); // foreign key 'faculty_id' in departments table
    }
}
