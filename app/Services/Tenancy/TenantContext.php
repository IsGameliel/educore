<?php

namespace App\Services\Tenancy;

use App\Models\Central\Institution;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TenantContext
{
    public function run(Institution $institution, callable $action, bool $provisioning = false, bool $financial = false): mixed
    {
        $close = $this->open($institution, $provisioning, $financial);
        try {
            return $action();
        } finally {
            $close();
        }
    }

    public function open(Institution $institution, bool $provisioning = false, bool $financial = false): \Closure
    {
        app(PlatformMaintenance::class)->ensureAvailable();
        $release = app(InstitutionMutex::class)->acquire($institution->id);
        try {
            app(PlatformMaintenance::class)->ensureAvailable();
            $close = $this->openUnlocked($institution, $provisioning, $financial);

            return static function () use ($close, $release) {
                try {
                    $close();
                } finally {
                    $release();
                }
            };
        } catch (\Throwable $exception) {
            $release();
            throw $exception;
        }
    }

    private function openUnlocked(Institution $institution, bool $provisioning, bool $financial): \Closure
    {
        $institution = $institution->fresh() ?? throw new RuntimeException('Institution no longer exists.');
        if (! $provisioning && $institution->status !== 'ready' && ! ($financial && in_array($institution->status, ['suspended', 'archived'], true))) {
            throw new RuntimeException('Institution is not ready.');
        }
        if (! $provisioning && ($institution->settings['dedicated_database_required'] ?? false) && ! app(InstitutionDatabases::class)->primary($institution->id)) {
            abort(503, 'Your institution database is being prepared. Please return to your institution account shortly.');
        }
        $name = config('tenancy.tenant_connection');
        if (DB::getDefaultConnection() === $name || $name === config('tenancy.central_connection')) {
            throw new RuntimeException('Nested or ambiguous tenant context is not allowed.');
        }
        $previousDefault = DB::getDefaultConnection();
        $previousConfiguration = config('database.connections.'.$name);
        $state = app(TenantState::class);
        $state->enter($institution->id);
        $resources = new TenantResources;
        $resourcesEntered = false;
        $closed = false;
        $close = function () use (&$closed, &$resourcesEntered, $resources, $name, $previousConfiguration, $previousDefault, $state) {
            if ($closed) {
                return;
            }
            $closed = true;
            try {
                if ($resourcesEntered) {
                    $resources->leave();
                }
            } finally {
                DB::purge($name);
                config(['database.connections.'.$name => $previousConfiguration]);
                DB::setDefaultConnection($previousDefault);
                $state->leave();
            }
        };
        try {
            DB::purge($name);
            $configuration = app(InstitutionDatabases::class)->connection($institution->id)->getConfig();
            unset($configuration['name']);
            config(['database.connections.'.$name => [...$configuration, 'url' => null, 'tenant_scoped' => true]]);
            DB::setDefaultConnection($name);
            $resourcesEntered = true;
            $resources->enter($institution);

            return $close;
        } catch (\Throwable $exception) {
            $close();
            throw $exception;
        }
    }
}
