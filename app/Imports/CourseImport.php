<?php

namespace App\Imports;

use App\Models\AcademicSession;
use App\Models\Courses;
use App\Models\User;
use App\Support\ActivityLogger;
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

    public function __construct(protected ?User $actor = null)
    {
    }

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
                'academic_session_id' => $this->resolveAcademicSessionId($row),
            ];

            $validator = Validator::make($data, [
                'code' => ['required', 'string', 'max:10'],
                'title' => ['required', 'string', 'max:255'],
                'credit_unit' => ['required', 'integer', 'min:1', 'max:10'],
                'semester' => ['required', Rule::in(['First', 'Second'])],
                'level' => ['required', Rule::in(['100', '200', '300', '400', '500', '600'])],
                'department_ids' => ['required', 'array', 'min:1'],
                'department_ids.*' => ['integer', 'exists:departments,id'],
                'academic_session_id' => ['required', 'exists:academic_sessions,id'],
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
                        'academic_session_id' => $data['academic_session_id'],
                    ],
                    [
                        'title' => $data['title'],
                        'credit_unit' => (int) $data['credit_unit'],
                    ]
                );

                if ($course->wasRecentlyCreated) {
                    $this->created++;
                    $this->logCourseCreated($course);
                    continue;
                }

                if ($course->title !== $data['title'] || (int) $course->credit_unit !== (int) $data['credit_unit']) {
                    $oldCreditUnit = $course->credit_unit;
                    $course->update([
                        'title' => $data['title'],
                        'credit_unit' => (int) $data['credit_unit'],
                    ]);
                    $this->logCourseUpdated($course->fresh(), $oldCreditUnit);
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

    protected function resolveAcademicSessionId($row): ?int
    {
        $rawSessionId = $row['academic_session_id'] ?? null;

        if (is_numeric($rawSessionId) && AcademicSession::whereKey((int) $rawSessionId)->exists()) {
            return (int) $rawSessionId;
        }

        $rawSessionName = trim((string) ($row['academic_session'] ?? $row['session'] ?? ''));

        if ($rawSessionName !== '') {
            return AcademicSession::where('name', $rawSessionName)->value('id');
        }

        return AcademicSession::current()?->id;
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

    protected function logCourseCreated(Courses $course): void
    {
        ActivityLogger::log(
            $this->actor,
            'course_created',
            "Added {$course->code} - {$course->title} ({$course->credit_unit} unit(s)) for {$course->level} Level",
            [
                'subject' => $course,
                'department_id' => $course->department_id,
                'properties' => [
                    'course_id' => $course->id,
                    'course_code' => $course->code,
                    'course_title' => $course->title,
                    'credit_unit' => $course->credit_unit,
                    'semester' => $course->semester,
                    'level' => (string) $course->level,
                    'academic_session_id' => $course->academic_session_id,
                    'academic_session' => $course->academicSession?->name,
                ],
            ]
        );
    }

    protected function logCourseUpdated(Courses $course, int $oldCreditUnit): void
    {
        $unitChanged = (int) $oldCreditUnit !== (int) $course->credit_unit;
        $description = $unitChanged
            ? "Updated {$course->code} - {$course->title} course unit from {$oldCreditUnit} to {$course->credit_unit}"
            : "Updated {$course->code} - {$course->title}";

        ActivityLogger::log(
            $this->actor,
            'course_updated',
            $description,
            [
                'subject' => $course,
                'department_id' => $course->department_id,
                'properties' => [
                    'course_id' => $course->id,
                    'course_code' => $course->code,
                    'course_title' => $course->title,
                    'credit_unit' => $course->credit_unit,
                    'old_credit_unit' => $oldCreditUnit,
                    'semester' => $course->semester,
                    'level' => (string) $course->level,
                    'academic_session_id' => $course->academic_session_id,
                    'academic_session' => $course->academicSession?->name,
                ],
            ]
        );
    }
}
