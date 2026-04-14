<?php

namespace App\Imports;

use App\Models\Courses;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CourseImport implements ToCollection, WithHeadingRow
{
    protected int $created = 0;
    protected int $skipped = 0;
    protected array $failedRows = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $row = $row->filter(fn ($value, $key) => ! is_numeric($key));

            $departmentIds = $this->extractDepartmentIds($row);

            $data = [
                'code' => trim((string) ($row['code'] ?? '')),
                'title' => trim((string) ($row['title'] ?? '')),
                'credit_unit' => $row['credit_unit'] ?? null,
                'semester' => trim((string) ($row['semester'] ?? '')),
                'level' => trim((string) ($row['level'] ?? '')),
                'department_ids' => $departmentIds,
            ];

            $validator = Validator::make($data, [
                'code' => ['required', 'string', 'max:10'],
                'title' => ['required', 'string', 'max:255'],
                'credit_unit' => ['required', 'integer', 'min:1', 'max:10'],
                'semester' => ['required', Rule::in(['First', 'Second'])],
                'level' => ['required', Rule::in(['100', '200', '300', '400', '500', '600'])],
                'department_ids' => ['required', 'array', 'min:1'],
                'department_ids.*' => ['integer', 'exists:departments,id'],
            ]);

            if ($validator->fails()) {
                $this->failedRows[] = [
                    'row' => $rowNumber,
                    'code' => $data['code'] ?: 'N/A',
                    'errors' => $validator->errors()->all(),
                ];
                continue;
            }

            foreach ($data['department_ids'] as $departmentId) {
                $course = Courses::firstOrCreate(
                    [
                        'code' => $data['code'],
                        'semester' => $data['semester'],
                        'level' => $data['level'],
                        'department_id' => $departmentId,
                    ],
                    [
                        'title' => $data['title'],
                        'credit_unit' => (int) $data['credit_unit'],
                    ]
                );

                if ($course->wasRecentlyCreated) {
                    $this->created++;
                    continue;
                }

                if ($course->title !== $data['title'] || (int) $course->credit_unit !== (int) $data['credit_unit']) {
                    $course->update([
                        'title' => $data['title'],
                        'credit_unit' => (int) $data['credit_unit'],
                    ]);
                }

                $this->skipped++;
            }
        }
    }

    protected function extractDepartmentIds($row): array
    {
        $rawValue = $row['department_ids'] ?? $row['department_id'] ?? '';

        if (is_array($rawValue)) {
            $values = $rawValue;
        } else {
            $values = preg_split('/[\s,;|]+/', (string) $rawValue, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '' && is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
    }

    public function createdCount(): int
    {
        return $this->created;
    }

    public function skippedCount(): int
    {
        return $this->skipped;
    }

    public function failedRows(): array
    {
        return $this->failedRows;
    }
}
