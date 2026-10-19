<?php

namespace App\Services\Tenancy;

use App\Models\Central\InstitutionDatabaseCopy;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** School-local IDs remain unchanged; central copies are namespaced by institution and table. */
class InstitutionCentralMirror
{
    public const EXCLUDED = ['institution_sessions', 'institution_password_resets', 'personal_access_tokens', 'sessions', 'password_reset_tokens', 'jobs', 'failed_jobs', 'job_batches', 'cache', 'cache_locks'];

    public function synchronize(InstitutionDatabaseCopy $copy): void
    {
        if (! $copy->enabled) {
            return;
        }
        if (app(TenantState::class)->id()) {
            throw new RuntimeException('Mirror must run outside a school request.');
        }
        $central = DB::connection(config('tenancy.central_connection'));
        if ($central->transactionLevel()) {
            throw new RuntimeException('Mirror requires a standalone process.');
        }
        $phase = 'connection';
        try {
            $this->record($copy, ['status' => 'running', 'last_attempt_at' => now(), 'failure_code' => null]);
            $source = app(InstitutionDatabases::class)->connection($copy->institution_id);
            $engines = collect($source->getSchemaBuilder()->getTables())->keyBy('name');
            if ($source->getSchemaBuilder()->getViews()) {
                throw new RuntimeException('Unmanaged views cannot be mirrored.');
            }
            $phase = 'snapshot';
            $source->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $source->transaction(function () use ($source, $central, $copy, $engines) {
                $central->transaction(function () use ($source, $central, $copy, $engines) {
                    $current = $central->table('institution_database_copies')->where('institution_id', $copy->institution_id)->lockForUpdate()->first();
                    if (! $current || ! $current->enabled || $current->direction !== 'inbound' || $current->configuration_version !== $copy->configuration_version) {
                        throw new RuntimeException('Configuration changed.');
                    }
                    $central->table('institution_record_snapshots')->where('institution_id', $copy->institution_id)->delete();
                    $manifest = [];
                    $total = $bytes = 0;
                    foreach ($source->getSchemaBuilder()->getTableListing($source->getDatabaseName(), schemaQualified: false) as $table) {
                        if (in_array($table, self::EXCLUDED, true) || ! $source->getSchemaBuilder()->hasColumn($table, 'tenant_id')) {
                            continue;
                        }
                        if (strtoupper($engines[$table]['engine'] ?? '') !== 'INNODB') {
                            throw new RuntimeException('Only transactional source tables can be mirrored.');
                        }
                        if (! preg_match('/\A[a-zA-Z0-9_]{1,64}\z/D', $table)) {
                            throw new RuntimeException('Invalid table.');
                        }
                        if ($source->table($table)->where('tenant_id', '!=', $copy->institution_id)->orWhereNull('tenant_id')->exists()) {
                            throw new RuntimeException('Foreign institution data.');
                        }
                        $query = $source->table($table)->where('tenant_id', $copy->institution_id);
                        $primary = collect($source->getSchemaBuilder()->getIndexes($table))->firstWhere('primary', true);
                        foreach ($primary['columns'] ?? $source->getSchemaBuilder()->getColumnListing($table) as $column) {
                            $query->orderBy($column);
                        }
                        $rows = 0;
                        $hash = hash_init('sha256');
                        $batch = [];
                        foreach ($query->cursor() as $row) {
                            $payload = base64_encode(serialize((array) $row));
                            $bytes += strlen($payload);
                            $total++;
                            $rows++;
                            if ($total > config('tenancy.database_copies.max_rows', 100000) || $bytes > config('tenancy.database_copies.max_bytes', 67108864)) {
                                throw new RuntimeException('Mirror quota exceeded.');
                            }
                            hash_update($hash, $payload);
                            $batch[] = ['institution_id' => $copy->institution_id, 'table_name' => $table, 'row_number' => $rows, 'payload' => $payload];
                            if (count($batch) >= 100) {
                                $central->table('institution_record_snapshots')->insert($batch);
                                $batch = [];
                            }
                        }
                        if ($batch) {
                            $central->table('institution_record_snapshots')->insert($batch);
                        }
                        $digest = hash_final($hash);
                        $verified = hash_init('sha256');
                        $count = 0;
                        foreach ($central->table('institution_record_snapshots')->where('institution_id', $copy->institution_id)->where('table_name', $table)->orderBy('row_number')->cursor() as $row) {
                            hash_update($verified, $row->payload);
                            $count++;
                        }
                        if ($count !== $rows || ! hash_equals($digest, hash_final($verified))) {
                            throw new RuntimeException('Mirror verification failed.');
                        }
                        $manifest[$table] = ['rows' => $rows, 'sha256' => $digest];
                    }
                    if (! $manifest) {
                        throw new RuntimeException('School schema missing.');
                    }
                    $users = $source->table('users')->where('tenant_id', $copy->institution_id)->whereNull('deleted_at')->whereNull('merged_into_id');
                    $statistics = ['students' => (clone $users)->where('usertype', 'student')->count(), 'staff' => (clone $users)->where('usertype', '!=', 'student')->count()];
                    $this->record($copy, ['status' => 'synced', 'last_synced_at' => now(), 'failure_code' => null, 'row_count' => $total,
                        'manifest' => json_encode($manifest, JSON_THROW_ON_ERROR), 'statistics' => json_encode($statistics, JSON_THROW_ON_ERROR)]);
                });
            });
        } catch (\Throwable) {
            $this->record($copy, ['status' => 'failed', 'failure_code' => $phase]);
            throw new RuntimeException('Central institution copy failed at '.$phase.'.');
        }
    }

    private function record(InstitutionDatabaseCopy $copy, array $values): void
    {
        DB::connection(config('tenancy.central_connection'))->table('institution_database_copies')->where('institution_id', $copy->institution_id)
            ->where('configuration_version', $copy->configuration_version)->update([...$values, 'updated_at' => now()]);
    }
}
