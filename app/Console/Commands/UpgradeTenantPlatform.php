<?php

namespace App\Console\Commands;

use App\Models\Central\Institution;
use App\Services\Backups\DatabaseBackups;
use App\Services\Tenancy\InstitutionMutex;
use App\Services\Tenancy\PlatformMaintenance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UpgradeTenantPlatform extends Command
{
    protected $signature = 'tenancy:upgrade {--writers-paused : Confirm web and worker writers are paused for this rollout}';

    protected $description = 'Back up and migrate the shared schema once, retaining maintenance on failure';

    public function handle(PlatformMaintenance $maintenance, InstitutionMutex $mutex): int
    {
        if (! $this->option('writers-paused')) {
            $this->error('Pause shared-platform writers, then supply --writers-paused.');

            return self::FAILURE;
        }
        $name = config('tenancy.central_connection');
        $original = config('database.connections.'.$name);
        $default = DB::getDefaultConnection();
        $id = (string) Str::uuid();
        $started = now();
        $backupPath = null;
        $releases = [];
        $upgradeLock = null;
        $success = false;
        try {
            if (app()->environment('production')) {
                $migrationUsername = getenv('TENANCY_MIGRATION_USERNAME') ?: config('tenancy.migration_username');
                $migrationPassword = getenv('TENANCY_MIGRATION_PASSWORD') ?: config('tenancy.migration_password');
                if (! $migrationUsername || ! $migrationPassword) {
                    throw new \RuntimeException('Configure dedicated TENANCY_MIGRATION_USERNAME and TENANCY_MIGRATION_PASSWORD for production upgrades.');
                }
                config(['database.connections.'.$name => [...$original, 'username' => $migrationUsername, 'password' => $migrationPassword, 'url' => null]]);
                DB::purge($name);
            }
            $db = DB::connection($name);
            if ($name === $default || $db->getDatabaseName() === DB::connection($default)->getDatabaseName()) {
                throw new \RuntimeException('Shared platform must differ from the legacy school database.');
            }
            if ($db->table('tenancy_schema')->value('architecture') !== 'shared_database') {
                throw new \RuntimeException('Shared platform ownership marker missing.');
            }
            if ($db->getDriverName() === 'mysql') {
                if ((int) $db->selectOne('SELECT GET_LOCK(?,0) AS acquired', ['educore.schema-upgrade'])->acquired !== 1) {
                    throw new \RuntimeException('Another shared upgrade is active.');
                }$upgradeLock = fn () => $db->select('SELECT RELEASE_LOCK(?)', ['educore.schema-upgrade']);
            } else {
                File::ensureDirectoryExists(config('tenancy.lock_directory'));
                $handle = fopen(config('tenancy.lock_directory').'/schema-upgrade.lock', 'c');
                if (! $handle || ! flock($handle, LOCK_EX | LOCK_NB)) {
                    throw new \RuntimeException('Another shared upgrade is active.');
                }$upgradeLock = static function () use ($handle) {
                    flock($handle, LOCK_UN);
                    fclose($handle);
                };
            }
            $maintenance->set(true, 'Shared schema upgrade '.$id);
            foreach (Institution::orderBy('id')->get() as $school) {
                $releases[] = $mutex->acquire($school->id);
            }
            $directory = config('backups.directory');
            try {
                DB::setDefaultConnection($name);
                config(['backups.directory' => storage_path('app/backups/shared-upgrades/'.$id)]);
                $manager = app(DatabaseBackups::class);
                $backup = $manager->create();
                $inspection = $manager->inspect($backup);
                if (! $inspection['complete'] || ! $inspection['compatible']) {
                    throw new \RuntimeException('Upgrade backup verification failed.');
                }
                $backupPath = $backup['path'];
                $this->info('Verified shared backup: '.$backupPath);
            } finally {
                DB::setDefaultConnection($default);
                config(['backups.directory' => $directory]);
            }
            config(['tenancy.upgrading' => true]);
            if (Artisan::call('tenancy:install', ['--no-interaction' => true]) !== 0) {
                throw new \RuntimeException('Shared migration failed. Maintenance remains active.');
            }
            $version = hash('sha256', json_encode($db->table('migrations')->orderBy('migration')->pluck('migration')->all()));
            $db->table('platform_schema_rollouts')->insert(['id' => $id, 'status' => 'success', 'backup_path' => $backupPath, 'schema_version' => $version, 'started_at' => $started, 'finished_at' => now()]);
            $maintenance->set(false, 'Shared upgrade verified');
            $success = true;
            $this->info('Shared schema upgraded and versioned; maintenance cleared. Legacy school unchanged.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            try {
                $db = DB::connection($name);
                if ($db->getSchemaBuilder()->hasTable('platform_schema_rollouts')) {
                    $db->table('platform_schema_rollouts')->updateOrInsert(['id' => $id], ['status' => 'failed', 'backup_path' => $backupPath, 'failure_type' => $e::class, 'started_at' => $started, 'finished_at' => now()]);
                }
            } catch (\Throwable) {
            }
            Log::error('Shared platform upgrade failed.', ['rollout_id' => $id, 'type' => $e::class]);
            $this->error('Upgrade failed ('.$e::class.'). If maintenance was enabled, it remains active. Inspect the rollout record and verified backup.');

            return self::FAILURE;
        } finally {
            foreach (array_reverse($releases) as $release) {
                $release();
            }if ($upgradeLock) {
                $upgradeLock();
            }
            config(['tenancy.upgrading' => false, 'database.connections.'.$name => $original]);
            DB::setDefaultConnection($default);
            DB::purge($name);
        }
    }
}
