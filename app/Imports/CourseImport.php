<?php

namespace App\Imports;

use App\Models\Courses;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class CourseImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $row = $row->filter(function ($value, $key) {
                return !is_numeric($key);
            });
            Courses::create([
                'code' => $row['code'],
                'title' => $row['title'],
                'credit_unit' => $row['credit_unit'],
                'semester' => $row['semester'],
                'department_id' => $row['department_id'],
                'level' => (string) $row['level'],
            ]);
        }
    }
}
