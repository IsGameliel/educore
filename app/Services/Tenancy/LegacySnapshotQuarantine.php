<?php

namespace App\Services\Tenancy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/** Rehearsal-only archival; production source data is never changed by this helper. */
class LegacySnapshotQuarantine
{
    public function apply(string $connection, array $preflight, string $archive): array
    {
        $db = DB::connection($connection);
        if (! preg_match('/\Aeducore_migration_[0-9]{8}_[a-f0-9]{12}_source\z/D', $db->getDatabaseName())) {
            throw new RuntimeException('Quarantine is restricted to disposable rehearsal source databases.');
        }
        $groups = [];
        foreach ($preflight['blocking_relationships'] as $issue) {
            if (! (($issue['table'] === 'teams' && $issue['column'] === 'user_id') || ($issue['table'] === 'class_schedules' && $issue['column'] === 'subject'))) {
                throw new RuntimeException('An unresolved relationship requires manual review before rehearsal archival.');
            }
            $groups[$issue['table']][$issue['id']] = true;
        }
        // Keep dependent attendance history with its invalid historical schedule.
        if (isset($groups['class_schedules'])) {
            foreach ($db->table('attendance_sessions')->whereIn('class_schedule_id', array_keys($groups['class_schedules']))->pluck('id') as $id) {
                $groups['attendance_sessions'][$id] = true;
            }
        }
        if (isset($groups['attendance_sessions'])) {
            foreach ($db->table('attendance_records')->whereIn('attendance_session_id', array_keys($groups['attendance_sessions']))->pluck('id') as $id) {
                $groups['attendance_records'][$id] = true;
            }
        }
        $tables = app(InstitutionArchives::class)->tables();
        $rows = [];
        foreach ($groups as $table => $ids) {
            $ids = array_keys($ids);
            $rows[$table] = $db->table($table)->whereIn('id', $ids)->get()->map(fn ($r) => (array) $r)->all();
            if ($table === 'teams' && collect($rows[$table])->contains(fn ($r) => ! $r['personal_team'])) {
                throw new RuntimeException('Shared teams require explicit review.');
            }
            foreach (array_keys($tables) as $other) {
                if (! $db->getSchemaBuilder()->hasTable($other)) {
                    continue;
                }foreach (app(TenantSchema::class)->foreignKeys($other) as $key) {
                    if ($key['foreign_table'] === $table && count($key['columns']) === 1 && $key['foreign_columns'] === ['id'] && $db->table($other)->whereIn($key['columns'][0], $ids)->when(isset($groups[$other]), fn ($q) => $q->whereNotIn('id', array_keys($groups[$other])))->exists()) {
                        throw new RuntimeException('Cannot archive referenced legacy '.$table.' rows.');
                    }
                }
            }
        }
        $patches = [];
        foreach ($preflight['conflicting_identifiers'] ?? [] as $conflict) {
            if ($conflict['table'] !== 'users' || $conflict['column'] !== 'matric_number') {
                throw new RuntimeException('Conflicting login identifiers require explicit review.');
            }
            foreach ($db->table('users')->whereIn('id', $conflict['ids'])->get(['id', 'matric_number']) as $user) {
                $patches[] = (array) $user;
            }
        }
        if (file_exists($archive)) {
            throw new RuntimeException('Rehearsal archive already exists.');
        }
        File::put($archive, json_encode(['source_database' => $db->getDatabaseName(), 'reason' => 'Isolated rehearsal archival only; owner review required for production', 'rows' => $rows, 'identifier_patches' => $patches], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        chmod($archive, 0600);
        $db->transaction(function () use ($db, $groups, $patches) {
            if ($patches) {
                $db->table('users')->whereIn('id', array_column($patches, 'id'))->update(['matric_number' => null]);
            }foreach (['attendance_records', 'attendance_sessions', 'class_schedules', 'teams'] as $table) {
                if (isset($groups[$table])) {
                    $db->table($table)->whereIn('id', array_keys($groups[$table]))->delete();
                }
            }
        });

        return ['cleared_conflicting_matric_fields' => count($patches), 'counts' => array_map('count', $rows), 'archive_sha256' => hash_file('sha256',$archive), 'production_review_required' => true];
    }
}
