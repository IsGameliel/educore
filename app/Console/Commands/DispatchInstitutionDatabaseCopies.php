<?php

namespace App\Console\Commands;

use App\Jobs\SynchronizeInstitutionDatabase;
use App\Models\Central\InstitutionDatabaseCopy;
use App\Services\Tenancy\PlatformMaintenance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DispatchInstitutionDatabaseCopies extends Command
{
    protected $signature = 'tenancy:copies-dispatch';

    protected $description = 'Queue configured institution database copies with stale-run recovery';

    public function handle(PlatformMaintenance $maintenance): int
    {
        $db = DB::connection(config('tenancy.central_connection'));
        if (! $db->getSchemaBuilder()->hasTable('institution_database_copies') || $maintenance->active()) {
            return self::SUCCESS;
        }
        $count = 0;
        foreach (InstitutionDatabaseCopy::where('enabled', true)->get() as $copy) {
            $claimed = $db->table('institution_database_copies')->where('institution_id', $copy->institution_id)
                ->where('configuration_version', $copy->configuration_version)
                ->where('enabled', true)->where(fn ($query) => $query->whereNull('queued_at')->orWhere('queued_at', '<=', now()->subMinutes(10)))
                ->update(['queued_at' => now(), 'status' => 'queued']);
            if ($claimed) {
                try {
                    SynchronizeInstitutionDatabase::dispatch($copy->institution_id);
                    $count++;
                } catch (\Throwable) {
                    $db->table('institution_database_copies')->where('institution_id', $copy->institution_id)
                        ->where('configuration_version', $copy->configuration_version)
                        ->update(['status' => 'failed', 'failure_code' => 'queue', 'queued_at' => null]);
                }
            }
        }
        $this->info('Institution database copies queued: '.$count);

        return self::SUCCESS;
    }
}
