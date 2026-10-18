<?php

namespace App\Services\Tenancy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PlatformMaintenance
{
    public function active(): bool
    {
        if (is_file(config('tenancy.maintenance_file', storage_path('framework/tenancy-maintenance.json')))) {
            return true;
        }
        $db = DB::connection(config('tenancy.central_connection'));

        return $db->getSchemaBuilder()->hasTable('platform_runtime') && $db->table('platform_runtime')->where('name', 'maintenance')->value('value') === '1';
    }

    public function set(bool $active, string $reason): void
    {
        $file = config('tenancy.maintenance_file', storage_path('framework/tenancy-maintenance.json'));
        if ($active) {
            File::put($file, json_encode(['reason' => $reason, 'started_at' => now()->toIso8601String()]));
        }
        $db = DB::connection(config('tenancy.central_connection'));
        if ($db->getSchemaBuilder()->hasTable('platform_runtime')) {
            $db->table('platform_runtime')->updateOrInsert(['name' => 'maintenance'], ['value' => $active ? '1' : '0', 'updated_at' => now()]);
        }
        if (! $active && is_file($file)) {
            File::delete($file);
        }
    }

    public function ensureAvailable(): void
    {
        abort_if($this->active(), 503, 'The platform is undergoing a supervised upgrade. Please retry shortly.');
    }
}
