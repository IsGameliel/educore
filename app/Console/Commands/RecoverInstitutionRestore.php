<?php

namespace App\Console\Commands;

use App\Models\Central\Institution;
use App\Services\Tenancy\InstitutionArchives;
use Illuminate\Console\Command;

class RecoverInstitutionRestore extends Command
{
    protected $signature = 'tenancy:recover-restore {institution : School slug} {--reason=}';

    protected $description = 'Recover an interrupted tenant restore using its journal without touching other schools';

    public function handle(InstitutionArchives $archives): int
    {
        $school = Institution::where('slug', $this->argument('institution'))->firstOrFail();
        try {
            $count = $archives->recover($school, (string) $this->option('reason'));
            $this->info('Restore journals recovered: '.$count);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Recovery requires operator inspection ('.$e::class.'). The school remains unavailable.');

            return self::FAILURE;
        }
    }
}
