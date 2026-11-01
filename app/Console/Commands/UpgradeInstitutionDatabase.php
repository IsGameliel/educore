<?php

namespace App\Console\Commands;

use App\Models\Central\InstitutionDatabaseCopy;
use App\Services\Backups\DatabaseBackups;
use App\Services\Tenancy\InstitutionDatabaseCopies;
use App\Services\Tenancy\InstitutionDatabases;
use App\Services\Tenancy\InstitutionMutex;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UpgradeInstitutionDatabase extends Command
{
    protected $signature = 'tenancy:database-upgrade {institution} {--writers-paused}';

    protected $description = 'Back up and migrate one dedicated institution database during supervised maintenance';

    public function handle(InstitutionDatabases $databases, InstitutionMutex $mutex): int
    {
        if (! $this->option('writers-paused') || ! app()->isDownForMaintenance()) {
            $this->error('Pause workers and enable application maintenance before upgrading a dedicated database.');

            return self::FAILURE;
        }
        $copy = InstitutionDatabaseCopy::where('direction', 'inbound')->findOrFail($this->argument('institution'));

        return $mutex->run($copy->institution_id, function () use ($copy, $databases) {
            $name = 'institution_schema_upgrade';
            $default = DB::getDefaultConnection();
            config(['database.connections.'.$name => $databases->configuration($copy)]);
            DB::purge($name);
            try {
                $db = DB::connection($name);
                $state = $db->table(InstitutionDatabaseCopies::STATE)->where('id', 1)->first();
                if (! $state || $state->institution_id !== $copy->institution_id || $state->source_tag !== 'dedicated-primary-v1') {
                    throw new RuntimeException('Ownership verification failed.');
                }
                DB::setDefaultConnection($name);
                $manager = app(DatabaseBackups::class);
                $backup = $manager->create();
                $inspection = $manager->inspect($backup);
                if (! $inspection['complete'] || ! $inspection['compatible']) {
                    throw new RuntimeException('Backup verification failed.');
                }
                $this->info('Verified institution backup: '.$backup['path']);
                if (Artisan::call('migrate', ['--database' => $name, '--path' => ['database/migrations', 'database/tenant-migrations'], '--force' => true]) !== 0) {
                    throw new RuntimeException('Migration failed.');
                }
                $copy->forceFill(['schema_version' => $databases->schemaVersion()])->save();
                $this->info('Dedicated institution schema verified for this release.');

                return self::SUCCESS;
            } catch (\Throwable) {
                $this->error('Dedicated schema upgrade failed. Keep maintenance enabled and inspect the verified backup.');

                return self::FAILURE;
            } finally {
                DB::setDefaultConnection($default);
                DB::purge($name);
                config(['database.connections.'.$name => null]);
            }
        });
    }
}
