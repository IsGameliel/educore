<?php

namespace App\Exports;

use App\Models\Courses;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ResultUploadTemplateSheet implements FromArray, WithTitle
{
    public function __construct(
        protected Courses $course,
        protected string $title
    ) {
    }

    public function array(): array
    {
        $rows = [
            ['MUDIAME UNIVERSITY IRRUA RESULT SHEET'],
            ['School', ''],
            ['Department', $this->course->department->name ?? ''],
            ['Level', $this->course->level ?? ''],
            ['Course code', $this->course->code],
            ['Course title', $this->course->title],
            ['Semester', $this->course->semester],
            ['S/NO', 'MATRIC NO.', 'NAME', 'CA', 'EXAM', 'Total'],

        ];
        $registrations = \App\Models\CourseRegistration::with('student')->where('course_id', $this->course->id)
            ->where('session', $this->course->academicSession?->name)->where('semester', $this->course->semester)
            ->whereIn('status', \App\Services\Academic\ResultRegistration::ELIGIBLE_STATUSES)
            ->orderBy('user_id')->get()->unique('user_id');
        foreach ($registrations as $registration) {
            $rows[] = [count($rows) - 7, $registration->student?->matric_number, $registration->student?->name, null, null, null];
        }
        return $rows;
    }

    public function title(): string
    {
        return $this->title;
    }
}
