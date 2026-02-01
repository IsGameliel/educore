<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacultiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faculties = [
            [
                'name' => 'Faculty of Science',
                'description' => 'This faculty focuses on the natural sciences, offering programs in physics, chemistry, biology, and related fields.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Faculty of Engineering',
                'description' => 'This faculty offers programs in various branches of engineering, including civil, mechanical, and electrical engineering.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Faculty of Arts',
                'description' => 'The Faculty of Arts includes programs in literature, history, philosophy, and other humanities.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Faculty of Agriculture',
                'description' => 'Dedicated to agricultural sciences and practices, focusing on modern farming and agribusiness.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Faculty of Social Sciences',
                'description' => 'This faculty offers programs in economics, political science, sociology, and psychology.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('faculties')->insert($faculties);
    }
}
