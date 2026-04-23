<?php

namespace App\Imports;

use App\Models\Courses;
use App\Models\Department;
use App\Models\Result;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;

class ResultsImport implements ToCollection
{
    protected const HEADER_ROW_NUMBER = 8;

    protected Courses $course;
    protected ?int $actorId;
    protected ?string $session;
    protected ?string $semester;
    protected array $report = [
        'processed' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    public function __construct(Courses $course, ?int $actorId = null, ?string $session = null, ?string $semester = null)
    {
        $this->course = $course;
        $this->actorId = $actorId;
        $this->session = $session;
        $this->semester = $semester;
    }

    public function collection(Collection $rows): void
    {
        $this->importRows($rows);
    }

    public function importRows(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded result sheet is empty.',
            ]);
        }

        $metadata = $this->extractMetadata($rows);
        $headerIndex = $this->findHeaderRowIndex($rows);

        $columnMap = $this->buildColumnMap($rows[$headerIndex] ?? []);
        $session = $this->session ?: ($metadata['session'] ?? null);

        if (!$session) {
            throw ValidationException::withMessages([
                'file' => 'Academic session is required in the uploaded sheet.',
            ]);
        }

        if (!empty($metadata['course_code']) && !$this->valuesMatch($metadata['course_code'], $this->course->code)) {
            throw ValidationException::withMessages([
                'file' => "The uploaded sheet is for course code {$metadata['course_code']}, but {$this->course->code} was selected.",
            ]);
        }

        $semester = $this->semester ?: $this->course->semester;
        $level = $metadata['level'] ?? $this->course->level;
        $effectiveLevel = $level ?: $this->course->level;
        $courseVariants = Courses::query()
            ->where('code', $this->course->code)
            ->where('semester', $semester)
            ->when($effectiveLevel, fn ($query) => $query->where('level', $effectiveLevel))
            ->get()
            ->keyBy('department_id');
        $courseVariantsFallback = Courses::query()
            ->where('code', $this->course->code)
            ->where('semester', $semester)
            ->get()
            ->keyBy('department_id');
        $linkedDepartmentNames = Department::query()
            ->whereIn('id', $courseVariantsFallback->keys()->all())
            ->orderBy('name')
            ->pluck('name')
            ->values();

        $students = User::where('usertype', 'student')
            ->get(['id', 'name', 'matric_number', 'level', 'department_id'])
            ->keyBy(fn (User $user) => $this->normalizeMatricNumber($user->matric_number));

        foreach ($rows->slice($headerIndex + 1) as $rowNumber => $row) {
            $cells = $this->normalizeRow($row);

            if ($this->rowIsEmpty($cells)) {
                continue;
            }

            $matricNumber = $this->stringAt($cells, $columnMap['matric_number']);
            if ($matricNumber === '') {
                continue;
            }

            $this->report['processed']++;

            $student = $students->get($this->normalizeMatricNumber($matricNumber));
            if (!$student) {
                $this->skipRow($rowNumber, "Student with matric number {$matricNumber} was not found in the system.");
                continue;
            }

            $resolvedCourse = $this->resolveCourseForStudent($student, $courseVariants, $courseVariantsFallback);

            if ($resolvedCourse === null) {
                $studentDepartmentName = Department::find($student->department_id)?->name ?? 'Unknown Department';
                $linkedDepartmentsText = $linkedDepartmentNames->isNotEmpty()
                    ? $linkedDepartmentNames->join(', ')
                    : 'no departments';

                $this->skipRow(
                    $rowNumber,
                    "Student with matric number {$matricNumber} belongs to {$studentDepartmentName}, but course {$this->course->code} is currently linked to {$linkedDepartmentsText}."
                );
                continue;
            }

            /** @var Courses $courseForStudent */
            $courseForStudent = $resolvedCourse['course'];
            $resultDepartmentId = $resolvedCourse['department_id'];
            $department = Department::find($resultDepartmentId);
            $passMark = $department?->pass_mark;

            $caScore = $this->numericAt($cells, $columnMap['ca_score']);
            $examScore = $this->numericAt($cells, $columnMap['exam_score']);
            $totalScore = $this->numericAt($cells, $columnMap['score']);

            if ($caScore !== null && $caScore > 30) {
                $this->skipRow($rowNumber, "CA score for {$matricNumber} is above the allowed maximum of 30.");
                continue;
            }

            if ($examScore !== null && $examScore > 70) {
                $this->skipRow($rowNumber, "Exam score for {$matricNumber} is above the allowed maximum of 70.");
                continue;
            }

            $score = Result::resolveScore($totalScore, $caScore, $examScore);
            if ($score === null || $score < 0 || $score > 100) {
                $this->skipRow($rowNumber, "Total score for {$matricNumber} is missing or invalid.");
                continue;
            }

            $gradeData = Result::calculateGradeAndPoint($score, $passMark);
            $resultKey = [
                'user_id' => $student->id,
                'session' => $session,
                'semester' => $semester,
                'level' => $level ?: $student->level,
                'course_code' => $courseForStudent->code,
                'department_id' => $resultDepartmentId,
                'source_result_id' => null,
            ];

            $attributes = [
                'uploaded_by' => $this->actorId,
                'matric_number' => $student->matric_number,
                'course_title' => $courseForStudent->title,
                'credit_unit' => $courseForStudent->credit_unit,
                'ca_score' => $caScore,
                'exam_score' => $examScore,
                'score' => $score,
                'grade' => $gradeData['grade'],
                'grade_point' => $gradeData['grade_point'],
            ];

            $existingResult = Result::where($resultKey)->first();

            if ($existingResult) {
                $existingResult->update($attributes);
                $result = $existingResult;
                $this->report['updated']++;
            } else {
                $result = Result::create($resultKey + $attributes);
                $this->report['created']++;
            }

            ActivityLogger::log(
                $this->actorId ? User::find($this->actorId) : null,
                $existingResult ? 'result_updated' : 'result_uploaded',
                ($existingResult ? 'Updated' : 'Uploaded') . " result for {$student->name} in {$courseForStudent->code} ({$semester} Semester, {$session})",
                [
                    'subject' => $result,
                    'target_user' => $student,
                    'department_id' => $resultDepartmentId,
                    'properties' => [
                        'course_id' => $courseForStudent->id,
                        'course_code' => $courseForStudent->code,
                        'course_title' => $courseForStudent->title,
                        'semester' => $semester,
                        'session' => $session,
                    ],
                ]
            );
        }

        if (($this->report['created'] + $this->report['updated']) === 0) {
            throw ValidationException::withMessages([
                'file' => $this->report['errors'][0] ?? 'No valid result rows were found in the uploaded sheet.',
            ]);
        }
    }

    public function getReport(): array
    {
        return $this->report;
    }

    protected function extractMetadata(Collection $rows): array
    {
        $metadata = [];

        foreach ($rows as $row) {
            $cells = $this->normalizeRow($row);

            foreach ($cells as $index => $cell) {
                if ($cell === '') {
                    continue;
                }

                $normalized = $this->normalizeLabel($cell);

                if (
                    !isset($metadata['course_code'])
                    && (
                        str_contains($normalized, 'course code')
                    )
                ) {
                    $metadata['course_code'] = $this->extractLabeledValue($cells, $index, $cell)
                        ?? $this->extractInlineMetadataValue($cell, ['course code']);
                }

                if (
                    !isset($metadata['course_title'])
                    && (
                        str_contains($normalized, 'course title')
                    )
                ) {
                    $metadata['course_title'] = $this->extractLabeledValue($cells, $index, $cell)
                        ?? $this->extractInlineMetadataValue($cell, ['course title']);
                }

                if (
                    !isset($metadata['session'])
                    && (
                        str_contains($normalized, 'academic session')
                        || $normalized === 'session'
                    )
                ) {
                    $metadata['session'] = $this->extractLabeledValue($cells, $index, $cell)
                        ?? $this->extractInlineMetadataValue($cell, ['academic session', 'session']);
                }

                if (!isset($metadata['level']) && preg_match('/([1-5]00)\s*level/i', $cell, $matches)) {
                    $metadata['level'] = $matches[1];
                } elseif (!isset($metadata['level']) && $normalized === 'level') {
                    $metadata['level'] = $this->extractLevelValue($cells, $index);
                }
            }
        }

        return $metadata;
    }

    protected function findHeaderRowIndex(Collection $rows): ?int
    {
        $headerIndex = self::HEADER_ROW_NUMBER - 1;
        $headerRow = $rows->get($headerIndex);

        if ($headerRow === null) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded sheet is too short. Row 8 must contain the result table header.',
            ]);
        }

        $cells = array_map(fn ($value) => $this->normalizeLabel((string) $value), $this->normalizeRow($headerRow));

        $hasMatric = collect($cells)->contains(fn ($cell) => str_contains($cell, 'matric'));
        $hasCa = collect($cells)->contains(fn ($cell) => preg_match('/^ca\b/', $cell) === 1);
        $hasExam = collect($cells)->contains(fn ($cell) => str_contains($cell, 'exam'));

        if (!($hasMatric && $hasCa && $hasExam)) {
            throw ValidationException::withMessages([
                'file' => 'Row 8 must contain the result table header with columns like Matric No., Name, CA, Exam, Total.',
            ]);
        }

        return $headerIndex;
    }

    protected function buildColumnMap($row): array
    {
        $cells = $this->normalizeRow($row);
        $map = [
            'matric_number' => null,
            'ca_score' => null,
            'exam_score' => null,
            'score' => null,
            'grade' => null,
        ];

        foreach ($cells as $index => $cell) {
            $normalized = $this->normalizeLabel($cell);

            if ($map['matric_number'] === null && str_contains($normalized, 'matric')) {
                $map['matric_number'] = $index;
            }

            if ($map['ca_score'] === null && preg_match('/^ca\b/', $normalized) === 1) {
                $map['ca_score'] = $index;
            }

            if ($map['exam_score'] === null && str_contains($normalized, 'exam')) {
                $map['exam_score'] = $index;
            }

            if ($map['score'] === null && (str_contains($normalized, 'total') || $normalized === 'score')) {
                $map['score'] = $index;
            }

            if ($map['grade'] === null && str_contains($normalized, 'grade')) {
                $map['grade'] = $index;
            }
        }

        if ($map['matric_number'] === null || $map['ca_score'] === null || $map['exam_score'] === null) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded sheet is missing one or more required columns: Matric No., CA, Exam.',
            ]);
        }

        return $map;
    }

    protected function extractLabeledValue(array $cells, int $index, string $cell): ?string
    {
        if (str_contains($cell, ':')) {
            $value = trim((string) preg_replace('/^[^:]+:/', '', $cell));
            if ($value !== '') {
                return $value;
            }
        }

        for ($nextIndex = $index + 1; $nextIndex < count($cells); $nextIndex++) {
            if ($cells[$nextIndex] !== '') {
                return $cells[$nextIndex];
            }
        }

        return null;
    }

    protected function extractLevelValue(array $cells, int $index): ?string
    {
        $value = $this->extractLabeledValue($cells, $index, $cells[$index] ?? '');

        if ($value !== null && preg_match('/([1-5]00)/', $value, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    protected function extractInlineMetadataValue(string $cell, array $labels): ?string
    {
        $trimmed = trim($cell);

        foreach ($labels as $label) {
            $pattern = '/^\s*' . preg_quote($label, '/') . '\s*[:\-]?\s+(.+?)\s*$/i';

            if (preg_match($pattern, $trimmed, $matches) === 1) {
                $value = trim($matches[1]);

                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    protected function normalizeRow($row): array
    {
        if ($row instanceof Collection) {
            $row = $row->toArray();
        }

        return array_values(array_map(fn ($value) => trim((string) $value), (array) $row));
    }

    protected function rowIsEmpty(array $cells): bool
    {
        foreach ($cells as $cell) {
            if ($cell !== '') {
                return false;
            }
        }

        return true;
    }

    protected function stringAt(array $cells, ?int $index): string
    {
        if ($index === null || !array_key_exists($index, $cells)) {
            return '';
        }

        return trim((string) $cells[$index]);
    }

    protected function numericAt(array $cells, ?int $index): ?float
    {
        $value = $this->stringAt($cells, $index);
        if ($value === '') {
            return null;
        }

        if (preg_match('/-?\d+(?:\.\d+)?/', str_replace(',', '', $value), $matches) !== 1) {
            return null;
        }

        return round((float) $matches[0], 2);
    }

    protected function normalizeMatricNumber(?string $value): string
    {
        return preg_replace('/\s+/', '', strtoupper(trim((string) $value)));
    }

    protected function normalizeLabel(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);

        return trim((string) $value);
    }

    protected function valuesMatch(?string $left, ?string $right): bool
    {
        $normalize = function (?string $value): string {
            return preg_replace('/[^a-z0-9]+/', '', strtolower(trim((string) $value)));
        };

        return $normalize($left) === $normalize($right);
    }

    protected function resolveCourseForStudent(User $student, Collection $courseVariants, Collection $courseVariantsFallback): ?array
    {
        $departmentId = (int) $student->department_id;
        $course = $courseVariants->get($departmentId)
            ?? $courseVariantsFallback->get($departmentId);

        if ($course) {
            return [
                'course' => $course,
                'department_id' => $departmentId,
            ];
        }

        if ($this->course->exists) {
            return [
                'course' => $this->course,
                'department_id' => $departmentId,
            ];
        }

        return null;
    }

    protected function skipRow(int $rowNumber, string $message): void
    {
        $this->report['skipped']++;
        $this->report['errors'][] = 'Row ' . ($rowNumber + 1) . ': ' . $message;
    }
}
