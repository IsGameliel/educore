<?php

namespace App\Console\Commands;

use App\Models\Central\Institution;
use Illuminate\Console\Command;

class WorkInstitutionQueues extends Command
{
    protected $signature = 'tenancy:work {--institution=} {--once} {--stop-when-empty} {--max-jobs=100}';

    protected $description = 'Run the central worker for institution queues with tenant-aware lifecycle hooks';

    public function handle(): int
    {
        $query = Institution::query();
        if ($slug = $this->option('institution')) {
            $query->where('slug', $slug);
        }
        $queues = $query->orderBy('id')->pluck('id')->map(fn ($id) => 'institution_'.$id)->implode(',');
        if (! $queues) {
            $this->info('No institution queues.');

            return self::SUCCESS;
        }

        return $this->call('queue:work', ['connection' => 'institutions', '--queue' => $queues, '--timeout' => 120, '--tries' => 3, '--backoff' => 60, '--sleep' => 1,
            '--max-time' => 300, '--max-jobs' => max(1, min(10000, (int) $this->option('max-jobs'))), '--once' => (bool) $this->option('once'), '--stop-when-empty' => (bool) $this->option('stop-when-empty')]);
    }
}
