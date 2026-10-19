<?php

namespace App\Console\Commands;

use App\Models\Central\InstitutionDatabaseCopy;
use App\Services\Backups\DatabaseBackups;
use App\Services\Tenancy\InstitutionDatabaseCopies;
use App\Services\Tenancy\InstitutionDatabases;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupInstitutionDatabases extends Command
{
    protected $signature = 'tenancy:databases-backup';

    protected $description = 'Create independent verified backups of all dedicated institution databases';

    public function handle(InstitutionDatabases $databases): int
    {
        $failed = false;
        foreach (InstitutionDatabaseCopy::where('direction', 'inbound')->cursor() as $copy) {
            $name = 'institution_backup';
            try {
                config(['database.connections.'.$name => $databases->configuration($copy)]);
                DB::purge($name);
                $db = DB::connection($name);
                $state = $db->table(InstitutionDatabaseCopies::STATE)->where('id', 1)->first();
                if (! $state || $state->institution_id !== $copy->institution_id || $state->source_tag !== 'dedicated-primary-v1') {
                    throw new \RuntimeException('Ownership mismatch');
                }
                $directory = storage_path('app/backups/dedicated-institutions/'.$copy->institution_id);
                app(DatabaseBackups::class)->scheduled($databases->configuration($copy), $directory, (int) config('backups.retention_days', 120));
                $this->info('Dedicated backup complete: '.$copy->institution_id);
            } catch (\Throwable) {
                $this->error('Dedicated backup failed: '.$copy->institution_id);
                $failed = true;
            } finally {
                DB::purge($name);
                config(['database.connections.'.$name => null]);
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
