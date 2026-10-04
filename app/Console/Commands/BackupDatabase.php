<?php

namespace App\Console\Commands;

use App\Services\Backups\DatabaseBackups;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database
        {--connection= : Database connection to back up}
        {--path= : Backup directory}
        {--cleanup-only : Only remove expired complete backups}';
    protected $description = 'Create a verified database backup using the dashboard backup service';

    public function handle(DatabaseBackups $backups): int
    {
        $monitor = ! $this->option('cleanup-only');
        if ($monitor) { \App\Services\OperationsHealth::record('backup', 'running'); }
        $connection = config('database.connections.'.($this->option('connection') ?: config('database.default')));
        if (! is_array($connection) || ($connection['driver'] ?? '') !== 'mysql' || empty($connection['database'])) {
            $this->error('A configured MySQL connection is required.');
            if ($monitor) { \App\Services\OperationsHealth::record('backup', 'failed', 'A configured MySQL connection is required.'); }
            return self::FAILURE;
        }
        try {
            $result = $backups->scheduled($connection, $this->option('path') ?: config('backups.directory').'/database',
                (int) config('backups.retention_days', 120), (bool) $this->option('cleanup-only'));
            if ($result['path']) { $this->info('Database backup created: '.$result['path']); }
            $this->info('Expired backups removed: '.$result['deleted']);
            if ($monitor) { \App\Services\OperationsHealth::record('backup', 'success', 'Verified backup completed.'); }
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::error('Scheduled database backup failed.', ['type' => $exception::class]);
            $this->error('Backup failed. Check database health, configured client tools and storage permissions.');
            if ($monitor) { \App\Services\OperationsHealth::record('backup', 'failed', 'Backup failed. Check database health, client tools and storage permissions.'); }
            return self::FAILURE;
        }
    }
}
