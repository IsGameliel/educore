<?php
namespace App\Imports;

use App\Models\Department;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DepartmentImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            Department::create([
                'name' => $row['name'],
                'description' => $row['description'],
                'faculty_id' => $row['faculty_id'],
            ]);
        }
    }
}
