<?php

namespace App\Services\Tenancy;

use App\Services\StudentAccountMerge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class LegacyDataResolution
{
    private function db()
    {
        if (app(TenantState::class)->id()) {
            throw new RuntimeException('Legacy reconciliation requires an operator context.');
        }
        $db = DB::connection();
        if ($db->getSchemaBuilder()->hasColumn('users', 'tenant_id') || $db->getSchemaBuilder()->hasTable('tenancy_schema')) {
            throw new RuntimeException('This command must not modify a shared platform.');
        }

        return $db;
    }

    private function normalized(string $name): array
    {
        $parts = preg_split('/\s+/u', mb_strtolower(trim($name)));
        sort($parts);

        return $parts;
    }

    public function plan(): array
    {
        $db = $this->db();
        $schema = $db->getSchemaBuilder();
        $orphans = $db->table('teams')->whereNotIn('user_id', $db->table('users')->select('id'))->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
        if (collect($orphans)->contains(fn ($r) => ! $r['personal_team'])) {
            throw new RuntimeException('Orphan shared teams require separate review.');
        }
        $schedules = $db->table('class_schedules')->whereNotIn('subject', $db->table('courses')->select('id'))->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
        $sessions = $db->table('attendance_sessions')->whereIn('class_schedule_id', array_column($schedules, 'id'))->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
        $attendance = $db->table('attendance_records')->whereIn('attendance_session_id', array_column($sessions, 'id'))->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
        $archive = ['teams' => $orphans, 'class_schedules' => $schedules, 'attendance_sessions' => $sessions, 'attendance_records' => $attendance];
        $tables = $schema->getTableListing($db->getDriverName() === 'sqlite' ? 'main' : $db->getDatabaseName(), schemaQualified: false);
        foreach ($archive as $target => $rows) {
            $ids = array_column($rows, 'id');
            if (! $ids) {
                continue;
            }
            foreach ($tables as $table) {
                $keys = $schema->getForeignKeys($table);
                if ($table === 'users') {
                    $keys[] = ['columns' => ['current_team_id'], 'foreign_table' => 'teams'];
                }
                foreach ($keys as $key) {
                    if ($key['foreign_table'] === $target && count($key['columns']) === 1) {
                        $q = $db->table($table)->whereIn($key['columns'][0], $ids);
                        if (isset($archive[$table])) {
                            $q->whereNotIn('id', array_column($archive[$table], 'id'));
                        }if ($q->exists()) {
                            throw new RuntimeException('Archival would affect a referenced '.$target.' record.');
                        }
                    }
                }
            }
        }
        $clear = [];
        $merges = [];
        $groups = $db->table('users')->select('matric_number')->whereNotNull('matric_number')->groupBy('matric_number')->havingRaw('COUNT(*)>1')->orderBy('matric_number')->get();
        foreach ($groups as $group) {
            $users = $db->table('users')->where('matric_number', $group->matric_number)->orderBy('id')->get();
            $active = $users->filter(fn ($u) => ! $u->merged_into_id && ! $u->deleted_at)->values();
            foreach ($users->filter(fn ($u) => $u->merged_into_id) as $user) {
                $target = $db->table('users')->where('id', $user->merged_into_id)->first();
                if (! $target || $target->matric_number !== $user->matric_number) {
                    throw new RuntimeException('Archived matric claim requires identity review.');
                }$clear[] = ['id' => $user->id, 'matric_number' => $user->matric_number, 'merged_into_id' => $user->merged_into_id];
            }
            if ($active->count() > 1) {
                $retained = $active->first();
                foreach ($active as $user) {
                    if ($user->usertype !== 'student' || $this->normalized($user->name) !== $this->normalized($retained->name) || $user->department_id !== $retained->department_id || ! $user->entry_year || $user->entry_year !== $retained->entry_year) {
                        throw new RuntimeException('Duplicate matric belongs to identities requiring manual review.');
                    }
                }
                $ids = $active->pluck('id')->all();
                $preview = app(StudentAccountMerge::class)->preview($ids, $retained->id);
                if ($preview['conflicts']) {
                    throw new RuntimeException('Duplicate student records have unresolved merge conflicts.');
                }
                $merges[] = ['ids' => $ids, 'retained_id' => $retained->id, 'digest' => $preview['digest'], 'evidence' => 'Same matric, normalized name, department and entry year; matching overlapping enrollment history', 'snapshots' => $preview['snapshots'], 'deduplication_groups' => count($preview['duplicates'])];
            }
        }
        $roots = ['private' => config('filesystems.disks.local.root'), 'public' => config('filesystems.disks.public.root')];
        // Audit expects sibling private/public roots. Legacy defaults satisfy this invariant.
        if (dirname($roots['private']) !== dirname($roots['public'])) {
            throw new RuntimeException('Review nonstandard legacy storage roots separately.');
        }
        $documents = app(LegacyDocumentAudit::class)->inspect(DB::getDefaultConnection(), dirname($roots['private']));
        $plan = ['database' => $db->getDatabaseName(), 'archived_rows' => $archive, 'clear_archived_matric' => $clear, 'merges' => $merges, 'missing_document_references' => $documents['missing_references'], 'document_references_checked' => $documents['references']];
        $plan['digest'] = hash_hmac('sha256', json_encode($plan, JSON_THROW_ON_ERROR), config('app.key'));

        return $plan;
    }

    public function apply(array $plan, string $archivePath, int $actor): array
    {
        $db = $this->db();
        $current = $this->plan();
        if (! hash_equals($current['digest'], $plan['digest'])) {
            throw new RuntimeException('Legacy data changed; prepare a fresh resolution plan.');
        }
        if (! $db->table('users')->where('id', $actor)->where('usertype', 'admin')->whereNull('merged_into_id')->whereNull('deleted_at')->exists()) {
            throw new RuntimeException('Select an active legacy administrator as audit actor.');
        }
        if (file_exists($archivePath)) {
            throw new RuntimeException('Resolution archive already exists.');
        }File::ensureDirectoryExists(dirname($archivePath), 0700);
        File::put($archivePath, json_encode(['created_at' => now()->toIso8601String(), 'policy' => 'legacy-data-resolution-v1', 'plan' => $plan, 'notes' => ['Archival retains invalid historical rows outside active tables.', 'Missing documents were reviewed, not fabricated or reissued.', 'Original credentials are preserved; archived duplicate access is revoked.']], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        chmod($archivePath, 0600);
        $checksum = hash_file('sha256', $archivePath);
        $result = $db->transaction(function () use ($db, $plan, $actor, $archivePath, $checksum) {
            foreach ($plan['merges'] as $merge) {
                app(StudentAccountMerge::class)->merge($merge['ids'], $merge['retained_id'], [], 'Owner requested legacy cleanup: '.$merge['evidence'], $actor, $merge['digest']);
            }
            foreach (['attendance_records', 'attendance_sessions', 'class_schedules', 'teams'] as $table) {
                $ids = array_column($plan['archived_rows'][$table], 'id');
                if ($ids) {
                    $db->table($table)->whereIn('id', $ids)->delete();
                }
            }
            foreach ($plan['clear_archived_matric'] as $user) {
                $db->table('users')->where('id', $user['id'])->where('merged_into_id', $user['merged_into_id'])->where('matric_number', $user['matric_number'])->update(['matric_number' => null]);
            }
            $cleared = 0;
            foreach ($plan['missing_document_references'] as $ref) {
                $cleared += $db->table($ref['table'])->where('id', $ref['id'])->where($ref['column'], $ref['path'])->update([$ref['column'] => null]);
            }
            $result = ['archived' => array_map('count', $plan['archived_rows']), 'merged_groups' => count($plan['merges']), 'archived_matric_claims_released' => count($plan['clear_archived_matric']), 'missing_references_cleared' => $cleared, 'archive_sha256' => $checksum];
            $db->table('activity_logs')->insert(['actor_id' => $actor, 'action' => 'legacy_data_resolved', 'description' => 'Owner-authorized legacy data reconciliation; originals retained in a private resolution archive.', 'properties' => json_encode(['origin' => 'operator_cli', 'archive' => basename($archivePath), 'sha256' => $checksum, 'resolution' => $result], JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);

            return $result;
        });

        return $result;
    }
}
