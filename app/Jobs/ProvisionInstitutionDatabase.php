<?php

namespace App\Jobs;

use App\Models\Central\Institution;
use App\Services\Tenancy\DirectAdminDatabases;
use App\Services\Tenancy\InstitutionDatabaseCopies;
use App\Services\Tenancy\InstitutionDatabases;
use App\Services\Tenancy\InstitutionMutex;
use App\Services\Tenancy\PlatformMaintenance;
use App\Services\Tenancy\SemesterBilling;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class ProvisionInstitutionDatabase implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 600;

    public array $backoff = [60, 180];

    public function __construct(public string $institutionId)
    {
        $this->onConnection('institution_copies')->onQueue('default');
    }

    public function uniqueId(): string
    {
        return $this->institutionId;
    }

    public function handle(): void
    {
        if (app(PlatformMaintenance::class)->active()) {
            $this->release(60);

            return;
        }
        $school = Institution::findOrFail($this->institutionId);
        if (! ($school->settings['dedicated_database_required'] ?? false) || ! app(SemesterBilling::class)->accessible($school)) {
            return;
        }
        if (app(InstitutionDatabases::class)->primary($school->id)) {
            return;
        }
        app(InstitutionMutex::class)->run($school->id, function () use ($school) {
            $copy = app(DirectAdminDatabases::class)->create($school);
            if (Artisan::call('tenancy:database-activate', ['institution' => $school->id, '--new-institution' => true]) !== 0) {
                throw new RuntimeException('New institution database activation failed.');
            }
            app(InstitutionDatabaseCopies::class)->synchronize($copy->fresh());
        });
    }
}
