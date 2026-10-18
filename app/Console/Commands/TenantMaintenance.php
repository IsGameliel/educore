<?php

namespace App\Console\Commands;

use App\Services\Tenancy\PlatformMaintenance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantMaintenance extends Command
{
    protected $signature = 'tenancy:maintenance {mode : on or off} {--reason=}';

    protected $description = 'Manage shared-platform maintenance; refusing recovery while migrations are pending';

    public function handle(PlatformMaintenance $maintenance): int
    {
        $mode = $this->argument('mode');
        $reason = $this->option('reason');
        if (! in_array($mode, ['on', 'off'], true) || ! is_string($reason) || strlen($reason) < 10) {
            $this->error('Supply on/off and a reason of at least ten characters.');

            return self::FAILURE;
        }
        if ($mode === 'off') {
            $db = DB::connection(config('tenancy.central_connection'));
            $files = app('migrator')->getMigrationFiles([config('tenancy.central_migrations'), ...config('tenancy.tenant_migrations')]);
            if (! $db->getSchemaBuilder()->hasTable('migrations') || array_diff(array_keys($files), $db->table('migrations')->pluck('migration')->all())) {
                $this->error('Shared migrations are still pending. Complete recovery before clearing maintenance.');

                return self::FAILURE;
            }
        }
        $maintenance->set($mode === 'on', $reason);
        $this->info('Shared maintenance '.$mode.'.');

        return self::SUCCESS;
    }
}
