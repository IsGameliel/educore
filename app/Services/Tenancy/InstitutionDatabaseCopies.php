<?php

namespace App\Services\Tenancy;

use App\Models\Central\InstitutionDatabaseCopy;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** One-way, verified snapshots. Never used by school requests as a primary connection. */
class InstitutionDatabaseCopies
{
    public const STATE = '_ev_copy_state';

    private const TRANSIENT = ['institution_sessions', 'institution_password_resets', 'personal_access_tokens',
        'sessions', 'password_reset_tokens', 'jobs', 'failed_jobs', 'job_batches', 'cache', 'cache_locks'];

    public static function supportsAtomicRename(string $version): bool
    {
        if (stripos($version, 'MariaDB') !== false) {
            return preg_match('/(\d+\.\d+\.\d+)-MariaDB/i', $version, $match)
                && version_compare($match[1], '10.6.1', '>=');
        }

        return preg_match('/\A(\d+\.\d+\.\d+)/', $version, $match)
            && version_compare($match[1], '8.0.0', '>=');
    }

    private function record(InstitutionDatabaseCopy $copy, array $values): void
    {
        if (isset($values['manifest'])) {
            $values['manifest'] = json_encode($values['manifest'], JSON_THROW_ON_ERROR);
        }
        DB::connection(config('tenancy.central_connection'))->table('institution_database_copies')
            ->where('institution_id', $copy->institution_id)->where('configuration_version', $copy->configuration_version)
            ->update([...$values, 'updated_at' => now()]);
    }

    public function validateTarget(string $database, string $username): void
    {
        $source = config('database.connections.'.config('tenancy.central_connection'));
        $default = config('database.connections.'.config('database.default'));
        if (! preg_match('/\A[a-zA-Z0-9_]{1,64}\z/D', $database)
            || in_array(strtolower($database), array_map('strtolower', [$source['database'], $default['database'], 'mysql', 'sys', 'information_schema', 'performance_schema']), true)
            || ! preg_match('/\A[a-zA-Z0-9_]{1,80}\z/D', $username)
            || in_array($username, [$source['username'] ?? '', $default['username'] ?? ''], true)) {
            throw new RuntimeException('Use a separate database and a dedicated user restricted to that database.');
        }
    }

    private function identifier(string $name): string
    {
        if (! preg_match('/\A[a-zA-Z0-9_]{1,64}\z/D', $name)) {
            throw new RuntimeException('Invalid schema identifier.');
        }

        return '`'.$name.'`';
    }

    private function temporary(string $table, string $kind): string
    {
        return '_ev_'.$kind.'_'.substr(hash('sha256', $table), 0, 24);
    }

    private function tableNames($connection): array
    {
        return $connection->getSchemaBuilder()->getTableListing($connection->getDatabaseName(), schemaQualified: false);
    }

    private function cleanPending($target, array $pending): void
    {
        foreach ($pending as $name) {
            if (! preg_match('/\A(?:_ev_[so]_[a-f0-9]{24}|_ev_copy_(?:old|next))\z/D', $name)) {
                throw new RuntimeException('Invalid copy recovery metadata.');
            }
            $target->statement('DROP TABLE IF EXISTS '.$this->identifier($name));
        }
    }

    public function synchronize(InstitutionDatabaseCopy $copy): void
    {
        if (($copy->direction ?? 'outbound') === 'inbound') {
            app(InstitutionCentralMirror::class)->synchronize($copy);

            return;
        }
        app(InstitutionMutex::class)->run($copy->institution_id, function () use ($copy) {
            $current = $copy->fresh();
            if (! $current || $current->configuration_version !== $copy->configuration_version || $current->direction === 'inbound') {
                return;
            }
            $this->synchronizeOutbound($copy);
        });
    }

    private function synchronizeOutbound(InstitutionDatabaseCopy $copy): void
    {
        if (app(TenantState::class)->id()) {
            throw new RuntimeException('Database copies must run outside institution request contexts.');
        }
        if (! $copy->enabled) {
            return;
        }
        $source = DB::connection(config('tenancy.central_connection'));
        if ($source->getDriverName() !== 'mysql' || $source->transactionLevel() !== 0) {
            throw new RuntimeException('Database copies require a standalone MySQL synchronization process.');
        }
        $connectionName = 'institution_copy';
        $original = config('database.connections.'.$connectionName);
        $locked = false;
        $target = null;
        $phase = 'configuration';
        try {
            $this->record($copy, ['status' => 'running', 'last_attempt_at' => now(), 'failure_code' => null]);
            $this->validateTarget($copy->database_name, $copy->username);
            $phase = 'connection';
            config(['database.connections.'.$connectionName => [...$source->getConfig(), 'url' => null,
                'database' => $copy->database_name, 'username' => $copy->username, 'password' => $copy->password,
                'tenant_scoped' => false, 'options' => [...($source->getConfig('options') ?? []), \PDO::ATTR_TIMEOUT => 10]]]);
            DB::purge($connectionName);
            $target = DB::connection($connectionName);
            $lock = 'euvion.copy.'.substr(hash('sha256', $copy->database_name), 0, 40);
            $locked = (int) $target->selectOne('SELECT GET_LOCK(?,0) AS acquired', [$lock])->acquired === 1;
            if (! $locked) {
                throw new RuntimeException('Copy already running.');
            }
            $phase = 'server_version';
            if (app()->environment('production') && ! self::supportsAtomicRename($target->selectOne('SELECT VERSION() AS version')->version)) {
                throw new RuntimeException('Production copies require MySQL 8+ or MariaDB 10.6.1+ for crash-safe activation.');
            }
            $phase = 'ownership';
            if ($target->getSchemaBuilder()->getViews()) {
                throw new RuntimeException('Target must not contain unmanaged views.');
            }
            $tag = hash('sha256', $source->getDatabaseName());
            $tables = $this->tableNames($target);
            if (! in_array(self::STATE, $tables, true)) {
                if ($tables) {
                    throw new RuntimeException('Target must be an empty database.');
                }
                $target->statement('CREATE TABLE '.$this->identifier(self::STATE).' (id TINYINT PRIMARY KEY, institution_id CHAR(36) NOT NULL, source_tag CHAR(64) NOT NULL, manifest LONGTEXT NOT NULL, pending LONGTEXT NOT NULL, synced_at DATETIME NULL) ENGINE=InnoDB');
                $target->table(self::STATE)->insert(['id' => 1, 'institution_id' => $copy->institution_id,
                    'source_tag' => $tag, 'manifest' => '{}', 'pending' => '[]']);
            }
            $state = $target->table(self::STATE)->where('id', 1)->first();
            if (! $state || $state->institution_id !== $copy->institution_id || $state->source_tag !== $tag) {
                throw new RuntimeException('Target belongs to a different institution or source.');
            }
            $old = json_decode($state->manifest, true, 512, JSON_THROW_ON_ERROR);
            $pending = json_decode($state->pending, true, 512, JSON_THROW_ON_ERROR);
            $allowed = [self::STATE, ...array_keys($old), ...$pending];
            if (array_diff($this->tableNames($target), $allowed)) {
                throw new RuntimeException('Unmanaged tables exist in target.');
            }
            $this->cleanPending($target, $pending);
            $owned = [];
            foreach ($this->tableNames($source) as $table) {
                if (! in_array($table, self::TRANSIENT, true) && $source->getSchemaBuilder()->hasColumn($table, 'tenant_id')) {
                    $this->identifier($table);
                    if (str_starts_with($table, '_ev_')) {
                        throw new RuntimeException('Reserved table prefix.');
                    }
                    $owned[] = $table;
                }
            }
            if (! $owned) {
                throw new RuntimeException('No institution schema found.');
            }
            $pending = ['_ev_copy_next', '_ev_copy_old'];
            foreach ($owned as $table) {
                $pending[] = $this->temporary($table, 's');
            }
            foreach (array_keys($old) as $table) {
                $pending[] = $this->temporary($table, 'o');
            }
            $target->table(self::STATE)->where('id', 1)->update(['pending' => json_encode($pending)]);
            $manifest = [];
            $total = 0;
            $phase = 'schema';
            foreach ($owned as $table) {
                $stage = $this->temporary($table, 's');
                $row = (array) $source->selectOne('SHOW CREATE TABLE '.$this->identifier($table));
                $ddl = $row['Create Table'];
                if (! str_contains($ddl, 'ENGINE=InnoDB')) {
                    throw new RuntimeException('Only transactional InnoDB source tables are supported.');
                }
                // Copies preserve columns and indexes. Cross-school/central FK targets are not copied.
                $lines = array_filter(explode("\n", $ddl), fn ($line) => ! preg_match('/^\s*(?:CONSTRAINT\s+`[^`]+`\s+)?FOREIGN KEY\b/i', $line));
                $ddl = preg_replace('/,\s*\n\)/', "\n)", implode("\n", $lines));
                $ddl = preg_replace('/\ACREATE TABLE `[^`]+`/', 'CREATE TABLE '.$this->identifier($stage), $ddl);
                $target->unprepared($ddl);
                $manifest[$table] = ['rows' => 0, 'sha256' => null];
            }
            $phase = 'snapshot';
            $source->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $source->transaction(function () use ($source, $target, $owned, $copy, &$manifest, &$total) {
                foreach ($owned as $table) {
                    $columns = collect($source->getSchemaBuilder()->getColumns($table))->filter(fn ($column) => empty($column['generation']))->pluck('name')->all();
                    $primary = collect($source->getSchemaBuilder()->getIndexes($table))->firstWhere('primary', true);
                    $order = $primary['columns'] ?? $columns;
                    $query = $source->table($table)->select($columns)->where('tenant_id', $copy->institution_id);
                    foreach ($order as $column) {
                        $query->orderBy($column);
                    }
                    $count = (clone $query)->count();
                    $total += $count;
                    if ($total > config('tenancy.database_copies.max_rows', 100000)) {
                        throw new RuntimeException('School exceeds configured synchronization row limit.');
                    }
                    $hash = hash_init('sha256');
                    $stage = $this->temporary($table, 's');
                    for ($offset = 0; $offset < $count; $offset += 250) {
                        $rows = (clone $query)->offset($offset)->limit(250)->get()->map(fn ($row) => (array) $row)->all();
                        foreach ($rows as $row) {
                            hash_update($hash, serialize($row));
                        }
                        $target->table($stage)->insert($rows);
                    }
                    $manifest[$table] = ['rows' => $count, 'sha256' => hash_final($hash)];
                    $verification = $target->table($stage)->select($columns);
                    foreach ($order as $column) {
                        $verification->orderBy($column);
                    }
                    $check = hash_init('sha256');
                    foreach ($verification->cursor() as $row) {
                        hash_update($check, serialize((array) $row));
                    }
                    if ($target->table($stage)->count() !== $count || ! hash_equals($manifest[$table]['sha256'], hash_final($check))) {
                        throw new RuntimeException('Copy verification failed.');
                    }
                }
            });
            $phase = 'activation';
            $current = $copy->fresh();
            if (! $current || ! $current->enabled || $current->configuration_version !== $copy->configuration_version) {
                throw new RuntimeException('Configuration changed during synchronization.');
            }
            $target->statement('CREATE TABLE `_ev_copy_next` LIKE '.$this->identifier(self::STATE));
            $garbage = ['_ev_copy_old'];
            $renames = [];
            foreach (array_keys($old) as $table) {
                $oldTable = $this->temporary($table, 'o');
                $renames[] = $this->identifier($table).' TO '.$this->identifier($oldTable);
                $garbage[] = $oldTable;
            }
            foreach ($owned as $table) {
                $renames[] = $this->identifier($this->temporary($table, 's')).' TO '.$this->identifier($table);
            }
            $target->table('_ev_copy_next')->insert(['id' => 1, 'institution_id' => $copy->institution_id, 'source_tag' => $tag,
                'manifest' => json_encode($manifest, JSON_THROW_ON_ERROR), 'pending' => json_encode($garbage), 'synced_at' => now()]);
            $renames[] = $this->identifier(self::STATE).' TO `_ev_copy_old`';
            $renames[] = '`_ev_copy_next` TO '.$this->identifier(self::STATE);
            // One MySQL statement atomically selects all verified tables and their manifest.
            $target->statement('RENAME TABLE '.implode(', ', $renames));
            $this->cleanPending($target, $garbage);
            $target->table(self::STATE)->where('id', 1)->update(['pending' => '[]']);
            $this->record($copy, ['status' => 'synced', 'last_synced_at' => now(), 'failure_code' => null,
                'row_count' => $total, 'manifest' => $manifest]);
        } catch (\Throwable $exception) {
            $this->record($copy, ['status' => 'failed', 'failure_code' => $phase]);
            // Never include a PDO exception, SQL bindings or credentials in queue logs/UI.
            throw new RuntimeException('Institution database synchronization failed at '.$phase.'.');
        } finally {
            if ($locked) {
                try {
                    $target->select('SELECT RELEASE_LOCK(?)', [$lock]);
                } catch (\Throwable) {
                }
            }
            DB::purge($connectionName);
            config(['database.connections.'.$connectionName => $original]);
        }
    }
}
