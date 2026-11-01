<?php

namespace App\Console\Commands;

use App\Models\Central\Institution;
use App\Models\Central\InstitutionDatabaseCopy;
use App\Services\Backups\DatabaseBackups;
use App\Services\Tenancy\InstitutionDatabaseCopies;
use App\Services\Tenancy\InstitutionDatabases;
use App\Services\Tenancy\InstitutionMutex;
use App\Services\Tenancy\PlatformAudit;
use App\Services\Tenancy\SemesterBilling;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ActivateInstitutionDatabase extends Command
{
    protected $signature = 'tenancy:database-activate {institution} {--writers-paused} {--new-institution}';

    protected $description = 'Back up, verify and promote an institution copy to its dedicated primary database';

    public function handle(InstitutionDatabaseCopies $copies, InstitutionDatabases $databases, InstitutionMutex $mutex): int
    {
        $school = Institution::findOrFail($this->argument('institution'));
        $new = $this->option('new-institution') && ($school->settings['dedicated_database_required'] ?? false)
            && $school->status === 'ready' && app(SemesterBilling::class)->accessible($school);
        if (! $new && (! $this->option('writers-paused') || ! app()->isDownForMaintenance())) {
            $this->error('Pause workers and put the application in maintenance before using --writers-paused.');

            return self::FAILURE;
        }

        return $mutex->run($school->id, function () use ($school, $copies, $databases) {
            $copy = InstitutionDatabaseCopy::findOrFail($school->id);
            if ($copy->direction === 'inbound') {
                $databases->connection($school->id)->table('users')->where('tenant_id', $school->id)->update(['remember_token' => null]);
                $this->info('This institution already uses its dedicated database.');

                return self::SUCCESS;
            }
            if (! $copy->enabled) {
                throw new RuntimeException('Enable the configured database copy before activation.');
            }
            $default = DB::getDefaultConnection();
            try {
                DB::setDefaultConnection(config('tenancy.central_connection'));
                $manager = app(DatabaseBackups::class);
                $backup = $manager->create();
                $inspection = $manager->inspect($backup);
                if (! $inspection['complete'] || ! $inspection['compatible']) {
                    throw new RuntimeException('Shared backup verification failed.');
                }
                $this->info('Verified shared backup: '.$backup['path']);
            } finally {
                DB::setDefaultConnection($default);
            }
            $name = 'institution_activation';
            config(['database.connections.'.$name => $databases->configuration($copy)]);
            DB::purge($name);
            $target = DB::connection($name);
            $central = DB::connection(config('tenancy.central_connection'));
            try {
                // Resume a process interrupted after changing the ownership marker but before central commit.
                $resuming = $target->getSchemaBuilder()->hasTable(InstitutionDatabaseCopies::STATE)
                    && in_array($target->table(InstitutionDatabaseCopies::STATE)->where('id', 1)->where('institution_id', $school->id)->value('source_tag'), ['primary-preparing-v1', 'dedicated-primary-v1'], true);
                if (! $resuming) {
                    $copies->synchronize($copy);
                }
                $copy->refresh();
                if (! $resuming && $copy->status !== 'synced') {
                    throw new RuntimeException('A verified copy is required.');
                }
                $target->table(InstitutionDatabaseCopies::STATE)->where('id', 1)->update(['source_tag' => 'primary-preparing-v1']);
                // Runtime sessions and resets must exist locally; no live tokens are migrated.
                foreach (['institution_sessions', 'institution_password_resets', 'personal_access_tokens', 'sessions', 'cache', 'cache_locks'] as $table) {
                    if (! $target->getSchemaBuilder()->hasTable($table)) {
                        $ddl = ((array) $central->selectOne('SHOW CREATE TABLE `'.$table.'`'))['Create Table'];
                        $lines = array_filter(explode("\n", $ddl), fn ($line) => ! preg_match('/^\s*(?:CONSTRAINT\s+`[^`]+`\s+)?FOREIGN KEY\b/i', $line));
                        $target->unprepared(preg_replace('/,\s*\n\)/', "\n)", implode("\n", $lines)));
                    }
                }
                foreach ($copy->manifest ?? [] as $table => $manifest) {
                    if (! $target->getSchemaBuilder()->hasTable($table) || $target->table($table)->count() !== $manifest['rows']) {
                        throw new RuntimeException('Activation row verification failed.');
                    }
                    $columns = collect($target->getSchemaBuilder()->getColumns($table))->filter(fn ($column) => empty($column['generation']))->pluck('name')->all();
                    $primary = collect($target->getSchemaBuilder()->getIndexes($table))->firstWhere('primary', true);
                    $query = $target->table($table)->select($columns);
                    foreach ($primary['columns'] ?? $columns as $column) {
                        $query->orderBy($column);
                    }
                    $hash = hash_init('sha256');
                    foreach ($query->cursor() as $row) {
                        hash_update($hash, serialize((array) $row));
                    }
                    if (! hash_equals($manifest['sha256'], hash_final($hash))) {
                        throw new RuntimeException('Activation checksum verification failed.');
                    }
                }
                if (! $target->getSchemaBuilder()->hasTable('migrations')) {
                    $target->statement('CREATE TABLE migrations (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(255) NOT NULL, batch INT NOT NULL) ENGINE=InnoDB');
                    foreach ($central->table('migrations')->orderBy('id')->get()->chunk(100) as $chunk) {
                        $target->table('migrations')->insert($chunk->map(fn ($row) => (array) $row)->all());
                    }
                }
                $this->restoreForeignKeys($central, $target, array_keys($copy->manifest ?? []));
                $target->table(InstitutionDatabaseCopies::STATE)->where('id', 1)->update(['source_tag' => 'dedicated-primary-v1']);
                $central->transaction(function () use ($copy, $school, $databases) {
                    $copy->forceFill(['direction' => 'inbound', 'primary_activated_at' => now(), 'schema_version' => $databases->schemaVersion(),
                        'configuration_version' => (string) Str::uuid(), 'queued_at' => null, 'last_synced_at' => null, 'status' => 'configured'])->save();
                    PlatformAudit::record('institution_database.primary_activated', $school->id, ['database_name' => $copy->database_name]);
                });
                $target->table('users')->update(['remember_token' => null]);
                $this->info('Dedicated primary activated. Queue synchronization to retain its records centrally.');

                return self::SUCCESS;
            } catch (\Throwable) {
                $copy->fresh()?->forceFill(['status' => 'failed', 'failure_code' => 'activation'])->save();
                $this->error('Activation failed. Keep maintenance enabled and inspect the institution connection and verified backup.');

                return self::FAILURE;
            } finally {
                DB::purge($name);
                config(['database.connections.'.$name => null]);
            }
        });
    }

    private function restoreForeignKeys($source, $target, array $tables): void
    {
        $quote = static function (string $name): string {
            if (! preg_match('/\A[a-zA-Z0-9_]{1,64}\z/D', $name)) {
                throw new RuntimeException('Invalid relationship identifier.');
            }

            return '`'.$name.'`';
        };
        foreach ($tables as $table) {
            $existing = collect($target->getSchemaBuilder()->getForeignKeys($table))->pluck('name')->all();
            foreach ($source->getSchemaBuilder()->getForeignKeys($table) as $key) {
                // Central institution metadata stays outside the school database.
                if (! in_array($key['foreign_table'], $tables, true) || in_array($key['name'], $existing, true)) {
                    continue;
                }
                $delete = strtoupper($key['on_delete']);
                $update = strtoupper($key['on_update']);
                foreach ([$delete, $update] as $action) {
                    if (! in_array($action, ['CASCADE', 'SET NULL', 'RESTRICT', 'NO ACTION'], true)) {
                        throw new RuntimeException('Unsupported relationship action.');
                    }
                }
                $target->statement('ALTER TABLE '.$quote($table).' ADD CONSTRAINT '.$quote($key['name']).' FOREIGN KEY ('.implode(',', array_map($quote, $key['columns'])).') REFERENCES '.$quote($key['foreign_table']).' ('.implode(',', array_map($quote, $key['foreign_columns'])).') ON DELETE '.$delete.' ON UPDATE '.$update);
            }
        }
    }
}
