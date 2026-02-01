<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class StudentsExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    protected Collection $students;

    public function __construct(Collection $students)
    {
        $this->students = $students;
    }

    public function collection()
    {
        return $this->students;
    }

    /**
     * Map each row to the desired columns: Name, Dept, Level, Email
     */
    public function map($student): array
    {
        return [
            $student->name,
            optional($student->department)->name,
            $student->level,
            $student->email,
        ];
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Department',
            'Level',
            'Email',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Insert placeholder logo at A1 (top-left)
                // Make sure placeholder exists at public/images/logo-placeholder.png
                $logoPath = public_path('images/logo-placeholder.png');

                if (file_exists($logoPath)) {
                    $drawing = new Drawing();
                    $drawing->setPath($logoPath);
                    $drawing->setCoordinates('A1');
                    $drawing->setHeight(60); // adjust as needed
                    $drawing->setWorksheet($sheet);
                }

                // Move headings down to row 4 so they don't overlap logo
                // We'll add a blank row above and set headings start row to 4
                // Shift all rows down by 3
                $sheet->insertNewRowBefore(1, 3);

                // Style headings row (row 4)
                $headingRow = 4;
                $sheet->getStyle("A{$headingRow}:D{$headingRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$headingRow}:D{$headingRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(30);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getColumnDimension('D')->setWidth(35);

                // Optional: freeze top rows so headings always visible
                $sheet->freezePane('A5');
            }
        ];
    }
}
