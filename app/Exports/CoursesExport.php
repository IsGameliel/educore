<?php

namespace App\Exports;

use App\Models\CourseRegistration;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CoursesExport implements FromCollection, WithHeadings
{
    protected $courses;

    public function __construct($courses)
    {
        $this->courses = $courses;
    }

    /**
     * Return the collection of courses to be exported.
     */
    public function collection()
    {
        // Check if the collection is not empty
        if ($this->courses->isEmpty()) {
            dd('No courses found');
        }

        return $this->courses->map(function ($course) {
            return [
                $course->course->course_code,  // Assuming 'course_code' is a field in the 'course' table
                $course->course->title,        // Assuming 'title' is a field in the 'course' table
                $course->course->credit_unit,  // Assuming 'credit_unit' is a field in the 'course' table
                $course->course->semester,     // Assuming 'semester' is a field in the 'course' table
                $course->status,               // Assuming 'status' is a field in the 'course_registration' table
            ];
        });
    }

    /**
     * Define the headings for the Excel file.
     */
    public function headings(): array
    {
        return [
            'Course Code',
            'Course Title',
            'Credit Unit',
            'Semester',
            'Status',
        ];
    }
}
