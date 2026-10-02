<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database
        {--connection= : The database connection name to back up}
        {--path= : The directory where backup files should be stored}
        {--cleanup-only : Only delete expired backups without creating a new one}';

    protected $description = 'Create a MySQL database backup and delete backups older than the configured retention period';

    public function handle(): int
    {
        $connectionName = $this->option('connection') ?: config('database.default');
        $connection = config("database.connections.{$connectionName}");

        if (! is_array($connection)) {
            $this->error("Database connection [{$connectionName}] is not configured.");

            return self::FAILURE;
        }

        if (($connection['driver'] ?? null) !== 'mysql') {
            $this->error('The backup command currently supports MySQL connections only.');

            return self::FAILURE;
        }

        $backupDirectory = $this->option('path') ?: storage_path('app/backups/database');
        $retentionDays = max((int) env('DB_BACKUP_RETENTION_DAYS', 120), 1);

        File::ensureDirectoryExists($backupDirectory);

        if ($this->option('cleanup-only')) {
            $deletedBackups = $this->pruneExpiredBackups($backupDirectory, $retentionDays);

            $this->info("Backup cleanup complete. Removed {$deletedBackups} expired backup(s).");

            return self::SUCCESS;
        }

        $databaseName = (string) ($connection['database'] ?? '');

        if ($databaseName === '') {
            $this->error("Database name is missing for connection [{$connectionName}].");

            return self::FAILURE;
        }

        $timestamp = now()->format('Y-m-d_His');
        $backupFile = "{$databaseName}_{$timestamp}.sql";
        $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.$backupFile;

        try {
            $this->createBackup($connection, $backupPath);
            $deletedBackups = $this->pruneExpiredBackups($backupDirectory, $retentionDays);
        } catch (Throwable $exception) {
            if (File::exists($backupPath)) {
                File::delete($backupPath);
            }

            Log::error('Database backup failed.', [
                'connection' => $connectionName,
                'path' => $backupPath,
                'exception' => $exception,
            ]);

            $this->error('Database backup failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Database backup created: {$backupPath}");
        $this->line("Expired backups removed: {$deletedBackups}");
        $this->line("Retention window: {$retentionDays} days");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    protected function createBackup(array $connection, string $backupPath): void
    {
        $binary = env('DB_BACKUP_BINARY', 'mysqldump');
        $databaseName = (string) ($connection['database'] ?? '');
        $username = (string) ($connection['username'] ?? '');
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '3306');

        $arguments = [
            $binary,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--result-file='.$backupPath,
            $databaseName,
        ];

        $socket = (string) ($connection['unix_socket'] ?? '');

        if ($socket !== '') {
            $arguments[] = '--socket='.$socket;
        }

        $environment = [];
        $password = $connection['password'] ?? null;

        if ($password !== null && $password !== '') {
            $environment['MYSQL_PWD'] = (string) $password;
        }

        $process = new Process($arguments, base_path(), $environment);
        $process->setTimeout(null);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'mysqldump did not complete successfully.');
        }

        if (! File::exists($backupPath) || File::size($backupPath) === 0) {
            throw new \RuntimeException('Backup file was not created or is empty.');
        }
    }

    protected function pruneExpiredBackups(string $backupDirectory, int $retentionDays): int
    {
        $deletedBackups = 0;
        $cutoffTimestamp = now()->subDays($retentionDays)->getTimestamp();

        foreach (File::files($backupDirectory) as $file) {
            if ($file->getExtension() !== 'sql') {
                continue;
            }

            if ($file->getMTime() >= $cutoffTimestamp) {
                continue;
            }

            File::delete($file->getPathname());
            $deletedBackups++;
        }

        return $deletedBackups;
    }
}
