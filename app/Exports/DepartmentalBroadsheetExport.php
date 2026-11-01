<?php

namespace App\Exports;

use App\Models\Courses;
use App\Services\Academic\AcademicStanding;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class DepartmentalBroadsheetExport extends StringValueBinder implements FromArray, WithCustomValueBinder, WithStyles, WithTitle
{
    public function __construct(
        public Collection $results,
        private string $department,
        private string $session,
        private ?string $semester,
        private bool $includeUnpublished = false,
        private bool $allSessions = false,
        private ?string $faculty = null,
    ) {}

    private function blocks(): Collection
    {
        return $this->results->sortBy(['session', 'level', 'semester', 'course_code', 'attempt_type'])
            ->groupBy(fn ($r) => json_encode([$r->session, $r->level, $r->semester]));
    }

    private function courseKey($result): string
    {
        return json_encode([$result->course_code, $result->attempt_type]);
    }

    private function summary(Collection $results): array
    {
        // Draft and reviewed marks may be displayed, but only published marks count toward GPA.
        $published = $results->where('workflow_status', 'published');
        if ($published->isEmpty()) {
            return ['', '', '', '', ''];
        }
        $policy = $published->sortBy('id')->last()->policy_snapshot;
        $rule = $policy['repeat_rule'] ?? 'all';
        $counted = AcademicStanding::counted($published, $rule);
        $passed = $published->filter(fn ($r) => $r->outcome_status === 'graded' && $r->grade_point > 0)->groupBy('course_code');
        $carryovers = $published->filter(fn ($r) => $r->outcome_status === 'graded' && (float) $r->grade_point === 0.0)
            ->pluck('course_code')->unique()->diff($passed->keys())->sort()->implode(', ');
        $gpa = AcademicStanding::gpa($published, $rule);

        return [
            $counted->sum('credit_unit'),
            $passed->sum(fn ($attempts) => $attempts->max('credit_unit')),
            $counted->sum(fn ($r) => $r->credit_unit * $r->grade_point),
            $gpa === null ? '' : number_format($gpa, 2),
            $carryovers ?: 'None',
        ];
    }

    public function array(): array
    {
        $courseStatuses = Courses::with('academicSession')
            ->whereIn('department_id', $this->results->pluck('department_id')->unique())
            ->whereIn('code', $this->results->pluck('course_code')->unique())->get()
            ->groupBy(fn ($course) => json_encode([$course->department_id, $course->academicSession?->name, $course->semester, $course->code]));
        $statusLabel = function ($result) use ($courseStatuses) {
            $key = json_encode([$result->department_id, $result->session, $result->semester, $result->course_code]);
            return $courseStatuses->get($key, collect())->pluck('status_code')->filter()->unique()->join('/');
        };
        $rows = [
            [config('institution.branding.name', config('app.name'))],
            [$this->faculty ?? ''],
            ['Department of '.$this->department],
            ["Students' Progress Result - ".($this->allSessions ? 'All available sessions through ' : '').$this->session.($this->includeUnpublished ? ' - REVIEW COPY' : '')],
            ['E: Elective; R: Required; C: Compulsory. TCC: credits counted; TCE: credits earned; TGP: weighted grade points. GPA and summaries use published results only. Unpublished cells show workflow status.'],
        ];
        $blocks = $this->blocks();
        $students = $this->results->groupBy('user_id')->sortBy(fn ($group) => $group->first()->matric_number);
        foreach (['Scores' => 'score', 'Letter Grade' => 'grade', 'Grade Point' => 'grade_point'] as $title => $field) {
            $rows[] = [$title];
            $groups = ['', '', ''];
            $headers = ['', '', ''];
            $units = ['S/N', 'Matric No.', 'Name'];
            foreach ($blocks as $block) {
                $first = $block->first();
                $courses = $block->unique(fn ($r) => $this->courseKey($r));
                $width = $courses->count() + 5;
                $label = ($first->level ? rtrim($first->level, 'Ll').'L' : 'Level unspecified').' - '.$first->session.' '.$first->semester.' Semester Examination';
                $groups = array_merge($groups, [$label], array_fill(0, $width - 1, ''));
                $headers = array_merge($headers, $courses->map(fn ($r) => $r->course_code.($r->attempt_type !== 'regular' ? ' ('.$r->attempt_type.')' : '').($statusLabel($r) !== '' ? ' ['.$statusLabel($r).']' : ''))->all(), ['TCC', 'TCE', 'TGP', 'GPA', 'Carry overs']);
                $units = array_merge($units, $courses->map(function ($r) use ($block) {
                    $credits = $block->filter(fn ($attempt) => $this->courseKey($attempt) === $this->courseKey($r))->pluck('credit_unit')->unique();
                    return $credits->count() === 1 ? $credits->first() : 'Varies';
                })->all(), ['', '', '', '', '']);
            }
            $rows[] = $groups;
            $rows[] = $headers;
            $rows[] = $units;
            $serial = 0;
            foreach ($students as $studentId => $studentResults) {
                $student = $studentResults->first();
                $row = [++$serial, $student->matric_number, $student->user?->name ?? ''];
                foreach ($blocks as $block) {
                    $attempts = $block->where('user_id', $studentId);
                    $byCourse = $attempts->groupBy(fn ($r) => $this->courseKey($r));
                    foreach ($block->unique(fn ($r) => $this->courseKey($r)) as $course) {
                        $row[] = $byCourse->get($this->courseKey($course), collect())->map(fn ($r) =>
                            ($r->outcome_status === 'graded' ? ($field === 'grade_point' && $r->{$field} !== null ? (float) $r->{$field} : ($r->{$field} ?? '')) : $r->outcome_status)
                            .($r->workflow_status !== 'published' ? ' ['.$r->workflow_status.']' : '')
                        )->implode('; ');
                    }
                    $row = array_merge($row, $this->summary($attempts));
                }
                $rows[] = $row;
            }
            $rows[] = [''];
            $rows[] = [''];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:'.$lastColumn.$lastRow)->getFont()->setName('Arial')->setSize(10);
        $sheet->getStyle('A1:'.$lastColumn.$lastRow)->getAlignment()->setVertical('center')->setHorizontal('center')->setWrapText(true);
        foreach ([1, 2, 3, 4, 5] as $row) {
            $sheet->mergeCells('A'.$row.':'.$lastColumn.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal('left');
            $sheet->getRowDimension($row)->setRowHeight($row === 5 ? 30 : 24);
        }
        $sheet->getStyle('A1:A4')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A5')->getFont()->setSize(9);
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(30);
        $studentCount = $this->results->pluck('user_id')->unique()->count();
        $blocks = $this->blocks();
        for ($section = 0; $section < 3; $section++) {
            $start = 6 + $section * ($studentCount + 6);
            $end = $start + 3 + $studentCount;
            $sheet->mergeCells('A'.$start.':'.$lastColumn.$start);
            $sheet->getStyle('A'.$start)->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A'.$start)->getAlignment()->setHorizontal('left');
            $sheet->getStyle('A'.($start + 1).':'.$lastColumn.$end)->getBorders()->getAllBorders()->setBorderStyle('thin');
            $sheet->getStyle('A'.($start + 1).':'.$lastColumn.($start + 3))->getFont()->setBold(true);
            $sheet->getRowDimension($start + 1)->setRowHeight(30);
            $sheet->getRowDimension($start + 2)->setRowHeight(42);
            $column = 4;
            foreach ($blocks as $block) {
                $count = $block->unique(fn ($r) => $this->courseKey($r))->count();
                $groupEnd = $column + $count + 4;
                $sheet->mergeCells(Coordinate::stringFromColumnIndex($column).($start + 1).':'.Coordinate::stringFromColumnIndex($groupEnd).($start + 1));
                for ($i = $column; $i <= $groupEnd; $i++) {
                    $letter = Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($letter)->setWidth($i === $groupEnd ? 22 : ($i < $column + $count ? 9 : 8))->setVisible(true);
                    if ($i >= $column + $count) {
                        $sheet->mergeCells($letter.($start + 2).':'.$letter.($start + 3));
                    }
                }
                $column = $groupEnd + 1;
            }
            for ($row = $start + 4; $row <= $end; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(32);
            }
        }
        $sheet->freezePane(null);
        $sheet->getProtection()->setSheet(false);
        $sheet->setShowGridlines(false);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A3)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setPrintArea('A1:'.$lastColumn.$lastRow);
        $sheet->getPageMargins()->setLeft(0.25)->setRight(0.25)->setTop(0.35)->setBottom(0.35);

        return [];
    }

    public function title(): string
    {
        return 'Departmental broadsheet';
    }
}
