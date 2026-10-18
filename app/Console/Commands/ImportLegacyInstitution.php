<?php

namespace App\Console\Commands;

use App\Models\Central\Institution;
use App\Services\Tenancy\InstitutionMutex;
use App\Services\Tenancy\LegacyInstitutionImport;
use App\Services\Tenancy\PlatformMaintenance;
use Illuminate\Console\Command;

class ImportLegacyInstitution extends Command
{
    protected $signature = 'tenancy:import-legacy {institution} {--source=} {--files=} {--execute} {--writers-paused} {--reason=}';

    protected $description = 'Review or import a separately restored legacy snapshot into an empty institution';

    public function handle(LegacyInstitutionImport $importer, InstitutionMutex $mutex): int
    {
        if (! is_string($this->option('source')) || ! config('database.connections.'.$this->option('source'))) {
            throw new \RuntimeException('Supply a configured restored snapshot source connection.');
        }
        $run = function () use ($importer) {
            $school = Institution::where('slug', $this->argument('institution'))->firstOrFail();
            $prepared = $importer->prepare($this->option('source'));
            $this->table(['Table', 'Rows'], collect($prepared['counts'])->map(fn ($count, $name) => [$name, $count])->values()->all());
            if (! $this->option('execute')) {
                $this->info('Review only. No target rows or files changed.');

                return self::SUCCESS;
            }
            if (! $this->option('writers-paused') || strlen($this->option('reason') ?? '') < 20) {
                throw new \RuntimeException('Pause all writers and provide an operator reason of at least twenty characters.');
            }
            if (! app(PlatformMaintenance::class)->active()) {
                throw new \RuntimeException('Enable shared maintenance before executing an import.');
            }
            $report = $importer->import($school, $prepared, (string) $this->option('files'));
            $this->info('Copied '.array_sum($report['counts']).' records and '.count($report['files']).' files. School remains blocked for review and agreed activation.');

            return self::SUCCESS;
        };

        return $this->option('execute') ? $mutex->run('legacy-import', $run) : $run();
    }
}
