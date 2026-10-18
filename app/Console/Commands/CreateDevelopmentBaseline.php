<?php

namespace App\Console\Commands;

use App\Services\Backups\DatabaseBackups;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class CreateDevelopmentBaseline extends Command
{
    protected $signature = 'development:baseline {--verify-restore : Rehearse SQL restore into a disposable database}';

    protected $description = 'Back up the existing institution database and application files with checksum verification';

    public function handle(DatabaseBackups $backups): int
    {
        $previousDirectory = config('backups.directory');
        $root = storage_path('app/backups/development-baselines/'.now()->format('Ymd-His').'-'.Str::random(8));
        $zip = new ZipArchive;
        try {
            File::ensureDirectoryExists($root, 0700);
            config(['backups.directory' => $root]);
            $backup = $backups->create();
            if ($this->option('verify-restore')) {
                $backups->verifySnapshot($backup);
            }
            $archive = $root.'/application-files.zip';
            if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
                throw new RuntimeException('Unable to create file backup.');
            }
            $files = [];
            $appRoot = realpath(storage_path('app'));
            $backupRoot = realpath(storage_path('app/backups'));
            $configuredBackupRoot = realpath($previousDirectory);
            foreach (File::allFiles($appRoot) as $file) {
                $path = $file->getRealPath();
                if (! $path || $file->isLink() || ! str_starts_with($path, $appRoot.DIRECTORY_SEPARATOR)
                    || ($backupRoot && str_starts_with($path, $backupRoot.DIRECTORY_SEPARATOR))
                    || ($configuredBackupRoot && str_starts_with($path, $configuredBackupRoot.DIRECTORY_SEPARATOR))) {
                    continue;
                }
                $relative = str_replace('\\', '/', substr($path, strlen($appRoot) + 1));
                if (! $zip->addFile($path, $relative)) {
                    throw new RuntimeException('File could not be archived.');
                }
                $files[$relative] = ['bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $path)];
            }
            if (! $zip->close()) {
                throw new RuntimeException('File archive could not be completed.');
            }
            if ($zip->open($archive, ZipArchive::CHECKCONS) !== true) {
                throw new RuntimeException('File archive integrity check failed.');
            }
            foreach ($files as $name => $metadata) {
                $stream = $zip->getStream($name);
                if (! $stream) {
                    throw new RuntimeException('Archived file is missing.');
                }
                $hash = hash_init('sha256');
                hash_update_stream($hash, $stream);
                fclose($stream);
                if (! hash_equals($metadata['sha256'], hash_final($hash))) {
                    throw new RuntimeException('Archived file checksum mismatch.');
                }
            }
            $zip->close();
            chmod($archive, 0600);
            $manifest = [
                'created_at' => now()->toIso8601String(), 'database' => DB::connection()->getDatabaseName(),
                'sql_file' => 'manual/'.$backup['name'], 'sql_sha256' => hash_file('sha256', $backup['path']),
                'sql_restore_rehearsed' => (bool) $this->option('verify-restore'),
                'files_archive' => basename($archive), 'files_archive_sha256' => hash_file('sha256', $archive),
                'files' => $files,
                'notes' => ['Storage files only; source and environment secrets require separate secure retention.',
                    'Database and files are captured sequentially; pause writers for a production-consistent snapshot.'],
            ];
            File::put($root.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            chmod($root.'/manifest.json', 0600);
            $this->info('Verified baseline created: '.$root);
            $this->line('Storage files verified: '.count($files).'; SQL restore rehearsed: '.($this->option('verify-restore') ? 'yes' : 'no'));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            // A bundle without its completion manifest must not be treated as verified.
            $this->error('Baseline failed ('.$exception::class.'). Preserve any partial files for inspection.');

            return self::FAILURE;
        } finally {
            config(['backups.directory' => $previousDirectory]);
        }
    }
}
