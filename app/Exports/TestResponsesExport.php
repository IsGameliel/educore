<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TestResponsesExport extends StringValueBinder implements FromArray, WithCustomValueBinder, WithStyles, WithColumnWidths
{
    public function __construct(public Collection $responses, private string $courseCode, private string $courseTitle) {}

    public function array(): array
    {
        return array_merge([['Name', 'Matric No', 'Course Code', 'Course Title', 'Score']], $this->responses->map(fn ($response) => [
            $response->student?->name ?? 'Unknown student',
            $response->student?->matric_number ?? '',
            $this->courseCode, $this->courseTitle, $response->score,
        ])->all());
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:E'.$sheet->getHighestRow());
        $sheet->getStyle('A1:E'.$sheet->getHighestRow())->getAlignment()->setWrapText(true);
        return [1 => ['font' => ['bold' => true]]];
    }

    public function columnWidths(): array
    {
        return ['A' => 32, 'B' => 25, 'C' => 20, 'D' => 45, 'E' => 12];
    }
}
