<?php

namespace App\Console\Commands;

use App\Models\Central\InstitutionDatabaseCopy;
use App\Services\Tenancy\InstitutionDatabases;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckInstitutionDatabases extends Command
{
    protected $signature = 'tenancy:databases-check';

    protected $description = 'Refuse deployment when a dedicated institution schema is behind this release';

    public function handle(InstitutionDatabases $databases): int
    {
        $db = DB::connection(config('tenancy.central_connection'));
        if (! $db->getSchemaBuilder()->hasColumn('institution_database_copies', 'direction')) {
            return self::SUCCESS;
        }
        $pending = InstitutionDatabaseCopy::where('direction', 'inbound')->where(function ($query) use ($databases) {
            $query->whereNull('schema_version')->orWhere('schema_version', '!=', $databases->schemaVersion());
        })->pluck('institution_id');
        if ($pending->isNotEmpty()) {
            $this->error('Dedicated institution schemas require supervised upgrade: '.$pending->implode(', '));

            return self::FAILURE;
        }
        $this->info('Dedicated institution schemas match this release.');

        return self::SUCCESS;
    }
}
