<?php

namespace App\Console\Commands;

use App\Services\Backups\DatabaseBackups;
use App\Services\Tenancy\LegacyDataResolution;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ResolveLegacyData extends Command
{
    protected $signature = 'legacy:resolve-data {--execute} {--digest=} {--actor=} {--writers-paused}';

    protected $description = 'Review or apply audited legacy archival, duplicate consolidation and missing-file reference cleanup';

    public function handle(LegacyDataResolution $resolution, DatabaseBackups $backups): int
    {
        $plan = $resolution->plan();
        $this->line('Resolution digest: '.$plan['digest']);
        $this->table(['Action', 'Count'], [...collect($plan['archived_rows'])->map(fn ($r, $t) => ['Archive '.$t, count($r)])->values()->all(), ['Consolidate duplicate identities', count($plan['merges'])], ['Release already archived matric claims', count($plan['clear_archived_matric'])], ['Review unavailable document references', count($plan['missing_document_references'])]]);
        if (! $this->option('execute')) {
            return self::SUCCESS;
        }
        if (! $this->option('writers-paused') || ! app()->isDownForMaintenance()) {
            throw new \RuntimeException('Enable application maintenance and pause writers first.');
        }
        if (! is_string($this->option('digest')) || ! hash_equals($plan['digest'], $this->option('digest'))) {
            throw new \RuntimeException('Supply the reviewed current digest.');
        }
        $actor = filter_var($this->option('actor'), FILTER_VALIDATE_INT);
        if (! $actor) {
            throw new \RuntimeException('Supply the existing administrator audit actor ID.');
        }
        $directory = config('backups.directory');
        $root = storage_path('app/backups/legacy-resolutions/'.now()->format('Ymd-His').'-'.Str::uuid());
        try {
            File::ensureDirectoryExists($root, 0700);
            config(['backups.directory' => $root]);
            $backup = $backups->create();
            $backups->verifySnapshot($backup);
            $result = $resolution->apply($plan, $root.'/resolution.json', $actor);
            File::put($root.'/completion.json', json_encode(['completed_at' => now()->toIso8601String(), 'database' => DB::connection()->getDatabaseName(), 'backup' => basename($backup['path']), 'backup_sha256' => hash_file('sha256', $backup['path']), 'result' => $result], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            chmod($root.'/completion.json', 0600);
            $this->info('Resolution applied after a verified restore rehearsal. Private evidence: '.$root);

            return self::SUCCESS;
        } finally {
            config(['backups.directory' => $directory]);
        }
    }
}
