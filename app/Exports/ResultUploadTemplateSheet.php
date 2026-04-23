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
        return [
            ['MUDIAME UNIVERSITY IRRUA RESULT SHEET'],
            ['School', ''],
            ['Department', $this->course->department->name ?? ''],
            ['Level', $this->course->level ?? ''],
            ['Course code', $this->course->code],
            ['Course title', $this->course->title],
            ['Semester', $this->course->semester],
            ['S/NO', 'MATRIC NO.', 'NAME', 'CA', 'EXAM', 'Total'],
            [1, 'MUI/SBMS/NS/24/0001', '', 18, 30, 48],
            [2, 'MUI/SBMS/NS/24/0002', '', 22, 38, 60],
        ];
    }

    public function title(): string
    {
        return $this->title;
    }
}
