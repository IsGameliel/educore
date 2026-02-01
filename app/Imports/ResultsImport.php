<?php

namespace App\Imports;

use App\Models\Result;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ResultsImport implements ToModel, WithHeadingRow
{
    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function model(array $row)
    {
        $user = User::where('id', $this->userId)->where('usertype', 'student')->first();
        if (!$user || $user->level != $row['level'] || $row['user_id'] != $this->userId) {
            return null; // Skip invalid rows
        }

        $gradeData = Result::calculateGradeAndPoint($row['score']);
        return new Result([
            'user_id' => $row['user_id'],
            'matric_number' => $row['matric_number'],
            'session' => $row['session'],
            'semester' => $row['semester'],
            'level' => $row['level'],
            'course_code' => $row['course_code'],
            'course_title' => $row['course_title'],
            'credit_unit' => $row['credit_unit'],
            'score' => $row['score'],
            'grade' => $row['grade'] ?? $gradeData['grade'],
            'grade_point' => $row['grade_point'] ?? $gradeData['grade_point'],
            'department_id' => $row['department_id']
        ]);
    }
}
