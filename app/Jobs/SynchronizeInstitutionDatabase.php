<?php

namespace App\Jobs;

use App\Models\Central\InstitutionDatabaseCopy;
use App\Services\Tenancy\InstitutionDatabaseCopies;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SynchronizeInstitutionDatabase implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [60, 180];

    public function __construct(public string $institutionId)
    {
        $this->onConnection('institution_copies')->onQueue('default');
    }

    public function handle(InstitutionDatabaseCopies $copies): void
    {
        $copy = InstitutionDatabaseCopy::find($this->institutionId);
        if ($copy && $copy->enabled) {
            $copies->synchronize($copy);
        }
    }
}
