<?php

namespace App\Services\Tenancy;

use App\Jobs\RunInstitutionTask;
use App\Models\Central\Institution;
use App\Services\OperationsHealth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TenantQueueScope
{
    private ?\Closure $close = null;

    private ?string $institutionId = null;

    public function before($event): void
    {
        $id = $event->job->payload()['institution_id'] ?? null;
        if (! $id) {
            if ($event->connectionName === 'institutions') {
                $event->job->delete();
                Log::warning('Discarded institution job without identity.');
            }

            return;
        }
        if ($event->job->getQueue() !== 'institution_'.$id) {
            $event->job->delete();

            return;
        }
        $institution = Institution::find($id);
        $financialTask = ($event->job->payload()['displayName'] ?? '') === RunInstitutionTask::class;
        if (! $institution || ! in_array($institution->status, ['ready', 'suspended', 'archived'], true)
            || ((! app(SemesterBilling::class)->accessible($institution) || $institution->status !== 'ready') && ! $financialTask)) {
            $event->job->delete();
            if ($institution) {
                InstitutionAudit::record($id, 'job_cancelled', 'worker', ['reason' => 'institution_unavailable', 'job_id' => $event->job->getJobId()]);
            }

            return;
        }
        $current = app(TenantState::class)->id();
        if ($current === $id) {
            return;
        }
        if ($current !== null) {
            throw new RuntimeException('A job cannot cross institution contexts.');
        }
        $this->close = app(TenantContext::class)->open($institution, financial: $financialTask);
        $this->institutionId = $id;
        OperationsHealth::record('queue', 'success', 'Institution worker started a job.');
    }

    public function after($event): void
    {
        if ($this->close && ($event->job->payload()['institution_id'] ?? null) === $this->institutionId) {
            try {
                ($this->close)();
            } finally {
                $this->close = null;
                $this->institutionId = null;
            }
        }
    }
}
