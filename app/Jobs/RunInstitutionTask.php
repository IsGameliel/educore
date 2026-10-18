<?php

namespace App\Jobs;

use App\Services\Tenancy\InstitutionTasks;
use App\Services\Tenancy\TenantState;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class RunInstitutionTask implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public string $institutionId, public string $task, public string $slot)
    {
        $this->onConnection('institutions')->onQueue('institution_'.$institutionId);
    }

    public function handle(): void
    {
        if (app(TenantState::class)->id() !== $this->institutionId) {
            throw new \RuntimeException('Scheduled job institution mismatch.');
        }
        app(InstitutionTasks::class)->execute($this->institutionId, $this->task, $this->slot);
    }

    public function failed(?\Throwable $exception): void
    {
        DB::connection(config('tenancy.central_connection'))->table('institution_task_runs')
            ->where('institution_id', $this->institutionId)->where('task', $this->task)->where('slot', $this->slot)
            ->where('status', '!=', 'success')->update(['status' => 'failed', 'failure_type' => $exception ? $exception::class : 'unknown', 'finished_at' => now()]);
    }
}
