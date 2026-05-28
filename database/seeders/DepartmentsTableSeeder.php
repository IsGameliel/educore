<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            // Faculty of Science
            [
                'name' => 'Department of Physics',
                'description' => 'This department focuses on the study of physics, offering undergraduate and graduate programs.',
                'faculty_id' => 1,  // Assuming Faculty of Science has ID 1
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Department of Chemistry',
                'description' => 'The Department of Chemistry offers programs in physical chemistry, organic chemistry, and biochemistry.',
                'faculty_id' => 1,  // Faculty of Science
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Department of Biology',
                'description' => 'This department covers various aspects of biological sciences, including molecular biology, ecology, and microbiology.',
                'faculty_id' => 1,  // Faculty of Science
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Faculty of Engineering
            [
                'name' => 'Department of Civil Engineering',
                'description' => 'The Department of Civil Engineering provides training in areas like structural engineering, geotechnical engineering, and transportation engineering.',
                'faculty_id' => 2,  // Faculty of Engineering
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Department of Electrical Engineering',
                'description' => 'This department specializes in electrical systems, electronics, telecommunications, and control systems.',
                'faculty_id' => 2,  // Faculty of Engineering
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Faculty of Arts
            [
                'name' => 'Department of Literature',
                'description' => 'The Department of Literature focuses on the study of various forms of literature, including English, African, and World literature.',
                'faculty_id' => 3,  // Faculty of Arts
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Department of Philosophy',
                'description' => 'This department offers programs in philosophy, ethics, and political thought.',
                'faculty_id' => 3,  // Faculty of Arts
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // You can add more departments for other faculties as needed.
        ];

        // Insert the data into the departments table
        DB::table('departments')->insert($departments);
    }
}
