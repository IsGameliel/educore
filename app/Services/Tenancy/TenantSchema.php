<?php

namespace App\Services\Tenancy;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class TenantSchema
{
    private array $owned = [];

    private array $foreignKeys = [];

    public function table(mixed $from): ?array
    {
        if ($from === null) {
            return null;
        }
        if (! is_string($from) || ! preg_match('/\A(?:(\w+)\.)?(\w+)(?:\s+(?:as\s+)?(\w+))?\z/i', $from, $match)) {
            throw new RuntimeException('Tenant queries require an explicit table or a scoped subquery.');
        }
        if (! empty($match[1]) && $match[1] !== DB::connection(config('tenancy.central_connection'))->getDatabaseName()) {
            throw new RuntimeException('Cross-database tenant queries are forbidden.');
        }

        return [$match[2], $match[3] ?? $match[2]];
    }

    public function owned(string $table): bool
    {
        return $this->owned[$table] ??= DB::connection(config('tenancy.central_connection'))->getSchemaBuilder()->hasColumn($table, 'tenant_id');
    }

    public function foreignKeys(string $table): array
    {
        if (isset($this->foreignKeys[$table])) {
            return $this->foreignKeys[$table];
        }
        $keys = DB::connection(config('tenancy.central_connection'))->getSchemaBuilder()->getForeignKeys($table);
        $known = ['user_id' => 'users', 'student_id' => 'users', 'lecturer_id' => 'users', 'acted_by' => 'users',
            'uploaded_by' => 'users', 'taken_by' => 'users', 'created_by' => 'users', 'updated_by' => 'users',
            'approved_by' => 'users', 'current_team_id' => 'teams', 'team_id' => 'teams', 'department_id' => 'departments',
            'faculty_id' => 'faculties', 'course_id' => 'courses', 'test_id' => 'tests', 'question_id' => 'questions',
            'academic_session_id' => 'academic_sessions', 'course_registration_id' => 'course_registrations',
            'source_result_id' => 'results', 'original_result_id' => 'results', 'merged_into_id' => 'users'];
        if ($table === 'class_schedules') {
            $known['subject'] = 'courses';
        }
        $columns = DB::connection(config('tenancy.central_connection'))->getSchemaBuilder()->getColumnListing($table);
        foreach ($known as $column => $foreign) {
            if (in_array($column, $columns, true) && ! collect($keys)->contains(fn ($key) => $key['columns'] === [$column])) {
                $keys[] = ['columns' => [$column], 'foreign_table' => $foreign, 'foreign_columns' => ['id']];
            }
        }

        return $this->foreignKeys[$table] = $keys;
    }
}
