<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentsExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(private Builder $payments) {}

    public function query()
    {
        return $this->payments->orderBy('id');
    }

    public function headings(): array
    {
        return ['Reference', 'Email', 'Purpose', 'Status', 'Currency', 'Gross kobo', 'Deductions kobo', 'Net kobo', 'Paid at'];
    }

    public function map($payment): array
    {
        return [$payment->reference, $payment->email, $payment->purpose, $payment->status,
            $payment->currency, (int) $payment->amount, (int) $payment->gateway_deduction,
            $payment->status === 'success' ? max(0, $payment->amount - $payment->gateway_deduction) : 0,
            $payment->paid_at?->toIso8601String()];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        // Keep references and user-supplied text literal, including formula-like values.
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
        return [1 => ['font' => ['bold' => true]]];
    }

    public function title(): string
    {
        return 'Payments';
    }
}
