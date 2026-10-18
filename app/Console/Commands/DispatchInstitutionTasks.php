<?php

namespace App\Console\Commands;

use App\Models\Central\Institution;
use App\Services\Tenancy\InstitutionTasks;
use App\Services\Tenancy\PlatformMaintenance;
use Illuminate\Console\Command;

class DispatchInstitutionTasks extends Command
{
    protected $signature = 'tenancy:dispatch {--institution= : Limit to one school slug} {--force : Create an additional manual run slot}';

    protected $description = 'Dispatch due institution jobs onto their central queues without charging subscriptions';

    public function handle(InstitutionTasks $tasks, PlatformMaintenance $maintenance): int
    {
        if ($maintenance->active()) {
            $this->warn('Shared platform maintenance is active.');

            return self::FAILURE;
        }
        $query = Institution::query();
        if ($slug = $this->option('institution')) {
            $query->where('slug', $slug);
        }
        $count = 0;
        $query->orderBy('id')->chunk(100, function ($schools) use ($tasks, &$count) {
            foreach ($schools as $school) {
                $count += $tasks->dispatchDue($school, (bool) $this->option('force'));
            }
        });
        $this->info('Institution jobs queued: '.$count);

        return self::SUCCESS;
    }
}
