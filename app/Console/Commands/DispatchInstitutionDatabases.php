<?php

namespace App\Console\Commands;

use App\Jobs\ProvisionInstitutionDatabase;
use App\Models\Central\Institution;
use App\Services\Tenancy\InstitutionDatabases;
use App\Services\Tenancy\PlatformMaintenance;
use App\Services\Tenancy\SemesterBilling;
use Illuminate\Console\Command;

class DispatchInstitutionDatabases extends Command
{
    protected $signature = 'tenancy:databases-dispatch';

    protected $description = 'Recover paid institutions awaiting their dedicated database setup';

    public function handle(): int
    {
        if (! config('tenancy.directadmin.enabled') || app(PlatformMaintenance::class)->active()) {
            return self::SUCCESS;
        }
        foreach (Institution::where('status', 'ready')->where('subscription_status', 'active')->cursor() as $school) {
            if (($school->settings['dedicated_database_required'] ?? false) && ! app(InstitutionDatabases::class)->primary($school->id) && app(SemesterBilling::class)->accessible($school)) {
                ProvisionInstitutionDatabase::dispatch($school->id);
            }
        }

        return self::SUCCESS;
    }
}
