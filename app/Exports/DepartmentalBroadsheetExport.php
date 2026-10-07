<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DepartmentalBroadsheetExport extends StringValueBinder implements FromArray, WithCustomValueBinder, WithStyles, WithTitle
{
    public function __construct(
        public Collection $results,
        private string $department,
        private string $session,
        private ?string $semester,
        private bool $includeUnpublished = false,
    ) {}

    public function array(): array
    {
        $key = fn ($result) => json_encode([$result->course_code, $result->semester, $result->attempt_type]);
        $courses = $this->results->sortBy(['course_code', 'semester', 'attempt_type'])->unique($key)->values();
        $rows = [
            [$this->includeUnpublished ? 'Departmental broadsheet — review copy' : 'Departmental broadsheet'],
            ['Department', $this->department],
            ['Academic session', $this->session],
            ['Semester', $this->semester ?: 'All semesters'],
            [$this->includeUnpublished ? 'REVIEW COPY: includes unpublished results. Workflow status is shown in each course cell.' : 'Reviewed, approved and published results. Unpublished marks show workflow status.'],
            array_merge(['Student', 'Matric number'], $courses->map(fn ($r) => $r->course_code.' ('.$r->semester.', '.$r->attempt_type.')')->all()),
        ];

        foreach ($this->results->groupBy('user_id')->sortBy(fn ($group) => $group->first()->user?->name) as $group) {
            $byCourse = $group->groupBy($key);
            $row = [$group->first()->user?->name, $group->first()->matric_number];
            foreach ($courses as $course) {
                $row[] = $byCourse->get($key($course), collect())->map(
                    fn ($r) => ($r->score ?? $r->outcome_status).' / '.($r->grade ?? '—').($this->includeUnpublished || $r->workflow_status !== 'published' ? ' ['.$r->workflow_status.']' : '')
                )->implode('; ');
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $sheet->getHighestColumn();
        $sheet->mergeCells('A1:'.$lastColumn.'1');
        $sheet->mergeCells('A5:'.$lastColumn.'5');
        $sheet->getStyle('A5')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension(5)->setRowHeight(32);
        // Freeze only headings so every column can scroll into view on small screens.
        $sheet->freezePane('A7');
        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(25);
        for ($column = 3; $column <= Coordinate::columnIndexFromString($lastColumn); $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth(24)->setVisible(true);
        }
        $sheet->getStyle('A6:'.$lastColumn.$sheet->getHighestRow())->getAlignment()->setWrapText(true)->setVertical('top');
        $sheet->getRowDimension(6)->setRowHeight(60);
        $sheet->getProtection()->setSheet(false);
        $sheet->setAutoFilter('A6:'.$sheet->getHighestColumn().$sheet->getHighestRow());

        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            6 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '234E70']]],
        ];
    }

    public function title(): string
    {
        return 'Departmental broadsheet';
    }
}
