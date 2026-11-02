<?php

namespace App\Exports;

use App\Models\Courses;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CourseCatalogExport implements FromCollection, WithHeadings
{
    public function __construct(
        private readonly ?string $title = null,
        private readonly ?string $departmentId = null,
        private readonly ?string $academicSessionId = null,
    ) {
    }

    public function collection()
    {
        $query = Courses::with('academicSession');

        if ($this->title !== null && $this->title !== '') {
            $query->where('title', 'like', '%'.$this->title.'%');
        }

        if ($this->departmentId !== null && $this->departmentId !== '') {
            $query->where('department_id', $this->departmentId);
        }

        if ($this->academicSessionId !== null && $this->academicSessionId !== '') {
            $query->where('academic_session_id', $this->academicSessionId);
        }

        return $query->orderBy('id')
            ->get()
            ->map(fn (Courses $course) => [
                'code' => $course->code,
                'title' => $course->title,
                'credit_unit' => $course->credit_unit,
                'semester' => $course->semester,
                'level' => $course->level,
                'department_ids' => $course->department_id,
                'academic_session_id' => $course->academic_session_id,
                'academic_session' => $course->academicSession?->name,
                'status' => $course->course_status,
            ]);
    }

    public function headings(): array
    {
        return [
            'code', 'title', 'credit_unit', 'semester', 'level',
            'department_ids', 'academic_session_id', 'academic_session', 'status',
        ];
    }
}
