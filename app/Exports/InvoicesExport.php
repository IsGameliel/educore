<?php

namespace App\Exports;

use Illuminate\Support\LazyCollection;
use Maatwebsite\Excel\Concerns\{FromGenerator, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithStyles, WithTitle};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DataType, DefaultValueBinder};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoicesExport extends DefaultValueBinder implements FromGenerator, WithHeadings, WithCustomValueBinder, ShouldAutoSize, WithStyles, WithTitle, WithColumnFormatting
{
    public function __construct(private LazyCollection $invoices) {}

    public function headings(): array
    {
        return ['Invoice', 'Student', 'Email', 'Matric number', 'Session', 'Department', 'Level', 'Category', 'Period', 'Currency', 'Tuition due', 'Net paid', 'Balance', 'Credit balance', 'Status', 'Overdue', 'Withdrawal review', 'Due date', 'Second due date'];
    }

    public function generator(): \Generator
    {
        foreach ($this->invoices as $invoice) {
            $totals = $invoice->totals();
            yield [$invoice->number, $invoice->student_name, $invoice->user?->email, $invoice->user?->matric_number,
                $invoice->session_name, $invoice->department_name, (string) $invoice->level, $invoice->category,
                $invoice->period, 'NGN', $totals['due'] / 100, $totals['paid'] / 100,
                $totals['balance'] / 100, $totals['overpayment'] / 100, $totals['status'],
                $invoice->overdue() ? 'Yes' : 'No', $invoice->needsWithdrawalReview() ? 'Yes' : 'No',
                $invoice->due_date?->format('Y-m-d'), $invoice->second_due_date?->format('Y-m-d')];
        }
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return array_fill_keys(['K', 'L', 'M', 'N'], '#,##0.00');
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
        return [1 => ['font' => ['bold' => true]]];
    }

    public function title(): string
    {
        return 'Invoices';
    }
}
