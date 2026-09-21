<?php

namespace App\Services\Backups;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseBackups
{
    public function listing(): array
    {
        $root = realpath(config('backups.directory'));
        if (! $root) {
            return [];
        }
        $backups = [];
        foreach (File::allFiles($root) as $file) {
            $path = $file->getRealPath();
            if ($file->isLink() || ! $path || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR) || $file->getExtension() !== 'sql') {
                continue;
            }
            $relative = substr($path, strlen($root) + 1);
            $backups[] = ['id' => hash('sha256', $relative), 'name' => $file->getFilename(),
                'folder' => dirname($relative), 'path' => $path, 'size' => $file->getSize(), 'modified' => $file->getMTime()];
        }
        usort($backups, fn ($a, $b) => $b['modified'] <=> $a['modified']);

        return $backups;
    }

    public function find(string $id): array
    {
        foreach ($this->listing() as $backup) {
            if (hash_equals($backup['id'], $id)) {
                return $backup;
            }
        }
        abort(404, 'Backup not found.');
    }

    public function inspect(array $backup): array
    {
        $handle = fopen($backup['path'], 'rb');
        if (! $handle) {
            throw new RuntimeException('The backup cannot be read.');
        }
        $header = fread($handle, 65536);
        fseek($handle, max(0, $backup['size'] - 4096));
        $tail = stream_get_contents($handle);
        rewind($handle);
        $tables = [];
        $unsafe = false;
        while (($line = fgets($handle)) !== false) {
            if (preg_match('/^INSERT INTO `([^`]+)`/', $line, $matches)) {
                $tables[$matches[1]] = true;
            }
            if (preg_match('/^\s*(USE\s|CREATE\s+DATABASE\s|DROP\s+DATABASE\s|SOURCE\s|\\\\!)/i', $line)) {
                $unsafe = true;
            }
        }
        fclose($handle);
        preg_match('/^-- Host:.*?Database:\s*(.*?)\s*$/m', $header, $match);
        $database = $match[1] ?? null;

        return ['database' => $database, 'tables_with_data' => array_keys($tables),
            'complete' => str_contains($tail, '-- Dump completed on '),
            'compatible' => ! $unsafe && $database === config('database.connections.'.config('database.default').'.database'),
            'checksum' => hash_file('sha256', $backup['path'])];
    }

    protected function connection(): array
    {
        $config = config('database.connections.'.config('database.default'));
        if (($config['driver'] ?? null) !== 'mysql' || empty($config['database'])) {
            throw new RuntimeException('Dashboard database backups and restores require a configured MySQL database.');
        }

        return $config;
    }

    protected function process(string $binary, array $options): Process
    {
        $config = $this->connection();
        $executable = is_file($binary) && is_executable($binary) ? $binary
            : (new ExecutableFinder)->find($binary, null, ['/usr/local/bin', '/opt/homebrew/bin', '/usr/bin']);
        if (! $executable) {
            throw new RuntimeException('MySQL client tool is unavailable. Configure DB_BACKUP_BINARY and DB_RESTORE_BINARY with executable paths.');
        }
        $arguments = [$executable, '--host='.($config['host'] ?? '127.0.0.1'), '--port='.($config['port'] ?? 3306),
            '--user='.($config['username'] ?? ''), '--default-character-set=utf8mb4'];
        if (! empty($config['unix_socket'])) {
            $arguments[] = '--socket='.$config['unix_socket'];
        }
        $process = new Process(array_merge($arguments, $options), base_path(), ['MYSQL_PWD' => $config['password'] ?? '']);
        $process->setTimeout(config('backups.timeout'));

        return $process;
    }

    protected function run(Process $process): void
    {
        if ($process->run() !== 0) {
            preg_match('/ERROR \d+(?: \([A-Z0-9]+\))?(?: at line \d+)?/', $process->getErrorOutput(), $match);
            $code = $match[0] ?? 'exit '.$process->getExitCode();
            Log::error('MySQL backup/restore subprocess failed', ['code' => $code]);
            throw new RuntimeException('MySQL operation failed ('.$code.'). Check server permissions, client tools and backup compatibility.');
        }
    }

    protected function exclusive(callable $action): mixed
    {
        if (function_exists('set_time_limit')) {
            set_time_limit(config('backups.timeout') + 60);
        }
        File::ensureDirectoryExists(config('backups.directory'), 0700);
        $lock = fopen(config('backups.directory').'/.operations.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) {
                fclose($lock);
            }
            throw new RuntimeException('Another dashboard backup or restore is running. Please wait and retry.');
        }
        try {
            return $action();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function create(): array
    {
        return $this->exclusive(fn () => $this->snapshot('manual'));
    }

    protected function snapshot(string $folder): array
    {
        $config = $this->connection();
        $directory = config('backups.directory').'/'.$folder;
        File::ensureDirectoryExists($directory, 0700);
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $config['database']).'_'.now()->format('Y-m-d_His').'_'.Str::random(8).'.sql';
        $path = $directory.'/'.$name;
        $partial = $path.'.partial';
        try {
            $this->run($this->process(config('backups.dump_binary'), [
                '--single-transaction', '--quick', '--routines', '--triggers', '--hex-blob', '--no-tablespaces', '--set-gtid-purged=OFF',
                '--result-file='.$partial, $config['database'],
            ]));
            if (! is_file($partial) || filesize($partial) === 0) {
                throw new RuntimeException('MySQL did not produce a backup file.');
            }
            $inspection = $this->inspect(['path' => $partial, 'size' => filesize($partial)]);
            if (! $inspection['complete'] || ! $inspection['compatible']) {
                throw new RuntimeException('The generated backup is incomplete or does not match this database.');
            }
            chmod($partial, 0600);
            rename($partial, $path);
        } catch (Throwable $exception) {
            if (is_file($partial)) {
                unlink($partial);
            }
            throw $exception;
        }

        return $this->find(hash('sha256', $folder.'/'.$name));
    }

    /** Remove dump-only replication state; retain all schema and record statements. */
    public function restoreInput(string $path)
    {
        $source = fopen($path, 'rb');
        $input = tmpfile();
        if (! $source || ! $input) {
            throw new RuntimeException('Unable to prepare a private restore file.');
        }
        $skipping = false;
        try {
            while (($line = fgets($source)) !== false) {
                if (preg_match('/^\s*SET\s+(?:@@GLOBAL\.GTID_PURGED|@@SESSION\.SQL_LOG_BIN|@MYSQLDUMP_TEMP_LOG_BIN)\s*=/i', $line)) {
                    $skipping = true;
                }
                if ($skipping) {
                    if (str_contains($line, ';')) {
                        $skipping = false;
                    }

                    continue;
                }
                if (fwrite($input, $line) !== strlen($line)) {
                    throw new RuntimeException('Could not prepare restore input.');
                }
            }
            if (! feof($source)) {
                throw new RuntimeException('Could not read the complete backup.');
            }
            if ($skipping) {
                throw new RuntimeException('Incomplete replication metadata in backup.');
            }
            rewind($input);

            return $input;
        } catch (Throwable $exception) {
            fclose($input);
            throw $exception;
        } finally {
            fclose($source);
        }
    }

    protected function importInput($input, string $database): void
    {
        rewind($input);
        $process = $this->process(config('backups.mysql_binary'), ['--binary-mode=1', '--local-infile=0', '--database='.$database]);
        $process->setInput($input);
        $this->run($process);
    }

    /** Prove both import and schema upgrades work before removing any live tables. */
    protected function preflight($input): void
    {
        $database = 'educore_restore_check_'.strtolower(Str::random(16));
        $connectionName = $database;
        $created = false;
        try {
            DB::statement('CREATE DATABASE `'.$database.'`');
            $created = true;
            $this->importInput($input, $database);
            config(['database.connections.'.$connectionName => array_merge($this->connection(), ['database' => $database, 'url' => null])]);
            if (Artisan::call('migrate', ['--database' => $connectionName, '--force' => true]) !== 0) {
                throw new RuntimeException('Backup schema upgrade failed in the temporary database.');
            }
            $connection = DB::connection($connectionName);
            foreach (['users', 'sessions', 'migrations'] as $table) {
                if (! $connection->getSchemaBuilder()->hasTable($table)) {
                    throw new RuntimeException('The restored backup is missing a required table: '.$table);
                }
            }
            if (! $connection->table('users')->where('usertype', 'admin')->exists()) {
                throw new RuntimeException('The selected backup has no administrator account.');
            }
        } finally {
            DB::purge($connectionName);
            config(['database.connections.'.$connectionName => null]);
            if ($created) {
                DB::statement('DROP DATABASE `'.$database.'`');
            }
        }
    }

    protected function clearTables(): void
    {
        DB::connection()->getSchemaBuilder()->dropAllViews();
        DB::connection()->getSchemaBuilder()->dropAllTables();
    }

    public function restore(string $id, string $checksum, int $actorId): array
    {
        return $this->exclusive(function () use ($id, $checksum, $actorId) {
            $this->connection();
            $backup = $this->find($id);
            $inspection = $this->inspect($backup);
            if (! $inspection['complete'] || ! $inspection['compatible'] || ! hash_equals($inspection['checksum'], $checksum)) {
                throw new RuntimeException('This backup is incomplete, incompatible or changed since confirmation. Open its restore page again.');
            }
            if (config('app.maintenance.driver') !== 'file' || app()->isDownForMaintenance()) {
                throw new RuntimeException('Restore requires file-based maintenance mode and a portal that is not already in maintenance.');
            }
            $input = $this->restoreInput($backup['path']);
            try {
                $this->preflight($input);
            } catch (Throwable $exception) {
                fclose($input);
                throw new RuntimeException('Backup validation failed before changing the portal database: '.$exception->getMessage(), 0, $exception);
            }
            try {
                if (Artisan::call('down', ['--retry' => 60]) !== 0) {
                    throw new RuntimeException('Could not enable maintenance mode. No restore was started.');
                }
                try {
                    // Failure here must never execute restore SQL or remove current tables.
                    $safety = $this->snapshot('before-dashboard-restore');
                } catch (Throwable $exception) {
                    Artisan::call('up');
                    throw new RuntimeException('The current database could not be backed up. Restore was stopped before changing any tables. Ask the server administrator to investigate database health.', 0, $exception);
                }
                Log::notice('Database restore starting', ['actor_id' => $actorId, 'backup' => $backup['name'], 'safety_backup' => $safety['name']]);
                try {
                    $this->clearTables();
                    $this->importInput($input, $this->connection()['database']);
                    DB::purge();
                    if (Artisan::call('migrate', ['--force' => true]) !== 0) {
                        throw new RuntimeException('Database schema upgrade failed.');
                    }
                    if (Artisan::call('up') !== 0) {
                        throw new RuntimeException('Could not leave maintenance mode.');
                    }
                    Log::notice('Database restore completed', ['actor_id' => $actorId, 'backup' => $backup['name']]);
                } catch (Throwable $exception) {
                    Log::error('Database restore failed; maintenance mode retained', ['actor_id' => $actorId, 'backup' => $backup['name'], 'safety_backup' => $safety['name']]);
                    throw new RuntimeException('Restore did not complete. The portal remains in maintenance mode. Ask the server administrator to recover using the safety backup '.$safety['name'].'.', 0, $exception);
                }

                return $safety;
            } finally {
                fclose($input);
            }
        });
    }
}
