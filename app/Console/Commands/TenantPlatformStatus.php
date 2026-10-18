<?php

namespace App\Console\Commands;

use App\Models\Central\Institution;
use Illuminate\Console\Command;
use Throwable;

class TenantPlatformStatus extends Command
{
    protected $signature = 'tenancy:status';

    protected $description = 'Show central institution provisioning states without exposing credentials';

    public function handle(): int
    {
        try {
            $this->table(['Institution', 'Plan', 'Status', 'Failure code'], Institution::orderBy('slug')->get()
                ->map(fn ($institution) => [$institution->slug, $institution->plan_code, $institution->status, $institution->failure_code ?? '-'])->all());

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Central platform is unavailable. Run tenancy:install after checking configuration.');

            return self::FAILURE;
        }
    }
}
