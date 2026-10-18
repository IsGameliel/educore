<?php

namespace App\Services\Tenancy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

class LegacyMigrationPreflight
{
    public function inspect(string $source): array
    {
        $db = DB::connection($source);
        $tables = app(InstitutionArchives::class)->tables();
        unset($tables['institution_profiles']);
        $rows = [];
        $ids = [];
        $issues = [];
        $historical = [];
        foreach ($tables as $table => $columns) {
            if (! $db->getSchemaBuilder()->hasTable($table)) {
                $issues[] = ['table' => $table, 'issue' => 'missing source table'];

                continue;
            }$rows[$table] = $db->table($table)->get()->map(fn ($r) => (array) $r)->all();
            foreach ($rows[$table] as $row) {
                $ids[$table][(string) $row['id']] = true;
            }
        }
        foreach ($rows as $table => $items) {
            $links = [];
            foreach (app(TenantSchema::class)->foreignKeys($table) as $key) {
                if ($key['columns'] !== ['tenant_id'] && count($key['columns']) === 1 && $key['foreign_columns'] === ['id']) {
                    $links[$key['columns'][0]] = $key['foreign_table'];
                }
            }
            foreach (['actor_id' => 'users', 'target_user_id' => 'users', 'approver_id' => 'users', 'tests_id' => 'tests', 'prerequisite_id' => 'courses'] as $column => $foreign) {
                if (in_array($column, $tables[$table], true)) {
                    $links[$column] = $foreign;
                }
            }
            foreach ($items as $row) {
                foreach ($links as $column => $foreign) {
                    if (isset($row[$column]) && ! isset($ids[$foreign][(string) $row[$column]])) {
                        $issues[] = ['table' => $table, 'id' => $row['id'], 'column' => $column, 'missing_table' => $foreign, 'missing_id' => $row[$column]];
                    }
                }
                foreach ([['subject_type', 'subject_id'], ['payable_type', 'payable_id']] as [$type,$column]) {
                    if (isset($row[$type],$row[$column])) {
                        $class = Relation::getMorphedModel($row[$type]) ?: $row[$type];
                        $foreign = is_subclass_of($class, Model::class) ? (new $class)->getTable() : null;
                        if (! $foreign || ! isset($ids[$foreign][(string) $row[$column]])) {
                            $issue = ['table' => $table, 'id' => $row['id'], 'column' => $column, 'missing_table' => $foreign, 'missing_id' => $row[$column]];
                            if ($table === 'activity_logs') {
                                $historical[] = $issue;
                            } else {
                                $issues[] = $issue;
                            }
                        }
                    }
                }
                foreach (match ($table) {
                    'responses','test_attempts' => ['answers' => 'questions'],'transcript_documents' => ['result_versions' => 'results'],default => []
                } as $column => $foreign) {
                    foreach (LegacyJson::array($row[$column]) as $id => $value) {
                        if (! isset($ids[$foreign][(string) $id])) {
                            $issues[] = ['table' => $table, 'id' => $row['id'], 'column' => $column, 'missing_table' => $foreign, 'missing_id' => $id];
                        }
                    }
                }
            }
        }
        $duplicates = [];
        foreach (['email', 'matric_number'] as $column) {
            $groups = $db->table('users')->select($column)->whereNotNull($column)->groupBy($column)->havingRaw('COUNT(*) > 1')->get();
            foreach ($groups as $group) {
                $duplicates[] = ['table' => 'users', 'column' => $column, 'ids' => $db->table('users')->where($column, $group->$column)->pluck('id')->all()];
            }
        }

        return ['conflicting_identifiers' => $duplicates, 'source_database' => $db->getDatabaseName(), 'ready' => $issues === [] && $duplicates === [], 'counts' => array_map('count', $rows), 'blocking_relationships' => $issues, 'historical_deleted_subjects' => count($historical)];
    }
}
