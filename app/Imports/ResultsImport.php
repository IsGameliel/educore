<?php

namespace App\Imports;

use App\Models\Courses;
use App\Models\Result;
use App\Models\User;
use App\Support\ActivityLogger;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class ResultsImport implements OnEachRow, WithHeadingRow
{
    protected $userId;
    protected $course;
    protected $actorId;

    public function __construct($userId, Courses $course, ?int $actorId = null)
    {
        $this->userId = $userId;
        $this->course = $course;
        $this->actorId = $actorId;
    }

    public function onRow(Row $importRow): void
    {
        $row = $importRow->toArray();
        $user = User::where('id', $this->userId)->where('usertype', 'student')->first();
        if (!$user || $user->level != $row['level'] || $row['user_id'] != $this->userId) {
            return;
        }

        if (($row['course_code'] ?? null) !== $this->course->code) {
            return;
        }

        if (($row['department_id'] ?? null) != $this->course->department_id) {
            return;
        }

        // fetch the department's pass mark
        $department = \App\Models\Department::find($this->course->department_id);
        $passMark = $department ? $department->pass_mark : null;

        $score = Result::resolveScore(
            $row['score'] ?? null,
            $row['ca_score'] ?? null,
            $row['exam_score'] ?? null
        );

        if ($score === null || $score > 100) {
            return;
        }

        $gradeData = Result::calculateGradeAndPoint($score, $passMark);
        $result = Result::create([
            'user_id' => $row['user_id'],
            'uploaded_by' => $this->actorId,
            'matric_number' => $row['matric_number'],
            'session' => $row['session'],
            'semester' => $row['semester'],
            'level' => $row['level'],
            'course_code' => $this->course->code,
            'course_title' => $this->course->title,
            'credit_unit' => $this->course->credit_unit,
            'ca_score' => $row['ca_score'] ?? null,
            'exam_score' => $row['exam_score'] ?? null,
            'score' => $score,
            'grade' => $row['grade'] ?? $gradeData['grade'],
            'grade_point' => $row['grade_point'] ?? $gradeData['grade_point'],
            'department_id' => $this->course->department_id,
        ]);

        ActivityLogger::log(
            $this->actorId ? User::find($this->actorId) : null,
            'result_uploaded',
            "Uploaded result for {$user->name} in {$this->course->code} ({$row['semester']} Semester, {$row['session']})",
            [
                'subject' => $result,
                'target_user' => $user,
                'department_id' => $this->course->department_id,
                'properties' => [
                    'course_id' => $this->course->id,
                    'course_code' => $this->course->code,
                    'course_title' => $this->course->title,
                    'semester' => $row['semester'],
                    'session' => $row['session'],
                ],
            ]
        );
    }
}
