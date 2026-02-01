<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Courses;
use Illuminate\Support\Facades\DB;

class CoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Number of courses to generate per level
        $coursesPerLevel = 30;

        // Define the levels
        $levels = [100, 200, 300, 400];

        // Define some sample departments (replace with real department IDs from your database)
        $departments = [1, 2, 3, 4, 5, 6, 7];

        // Define sample course titles for each level
        $courseTitles = [
            100 => [
                'Introduction to Computer Science',
                'Mathematics for Computer Science',
                'Introduction to Programming',
                'Basic Information Technology',
                'Introduction to Algorithms',
            ],
            200 => [
                'Data Structures and Algorithms',
                'Database Management Systems',
                'Object-Oriented Programming',
                'Computer Networks',
                'Operating Systems',
            ],
            300 => [
                'Software Engineering',
                'Web Development',
                'Mobile Application Development',
                'Data Science',
                'Artificial Intelligence',
            ],
            400 => [
                'Advanced Algorithms',
                'Cloud Computing',
                'Machine Learning',
                'Big Data Analytics',
                'Cyber Security',
            ],
        ];

        // Store created courses for assigning prerequisites
        $createdCourses = [];

        // Loop through the levels
        foreach ($levels as $level) {
            // Generate courses for each level
            for ($i = 0; $i < $coursesPerLevel; $i++) {
                $courseData = [
                    'code' => 'CS' . $level . str_pad($i + 1, 3, '0', STR_PAD_LEFT), // e.g., CS100001
                    'title' => $courseTitles[$level][$i % count($courseTitles[$level])], // Cycle through titles
                    'credit_unit' => rand(2, 4), // Random credit unit between 2 and 4
                    'semester' => $i % 2 === 0 ? 'First Semester' : 'Second Semester', // Alternate semesters
                    'department_id' => $departments[array_rand($departments)], // Random department ID
                ];

                // Create the course
                $course = Courses::create($courseData);

                // Store the course for prerequisite assignment
                $createdCourses[$level][] = $course;
            }
        }

        // Assign prerequisites
        foreach ($createdCourses as $level => $courses) {
            if ($level > 100) { // Skip 100-level courses (no prerequisites)
                $previousLevel = $createdCourses[$level - 100] ?? [];

                foreach ($courses as $course) {
                    // Randomly select prerequisites from the previous level
                    $prerequisites = array_rand($previousLevel, rand(1, 3)); // 1-3 prerequisites
                    $prerequisiteCourses = is_array($prerequisites) ? $prerequisites : [$prerequisites];

                    foreach ($prerequisiteCourses as $prerequisiteKey) {
                        $course->prerequisites()->attach($previousLevel[$prerequisiteKey]->id);
                    }
                }
            }
        }
    }
}
