<?php

namespace App\Models\Concerns;

use App\Services\Tenancy\TenantState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

trait BelongsToInstitution
{
    public static function bootBelongsToInstitution(): void
    {
        static::addGlobalScope('institution', function (Builder $query) {
            $model = $query->getModel();
            if ($model->usesSharedInstitutionConnection()) {
                $id = app(TenantState::class)->id();
                if ($id === null) {
                    throw new RuntimeException('An institution context is required for school records.');
                }
                $query->where($model->qualifyColumn('tenant_id'), $id);
            }
        });
        static::creating(function ($model) {
            if ($model->usesSharedInstitutionConnection()) {
                $id = app(TenantState::class)->id();
                if ($id === null || ($model->tenant_id !== null && $model->tenant_id !== $id)) {
                    throw new RuntimeException('Invalid institution ownership.');
                }
                $model->tenant_id = $id;
            }
        });
        static::updating(function ($model) {
            if ($model->usesSharedInstitutionConnection()
                && ($model->tenant_id !== app(TenantState::class)->id() || $model->isDirty('tenant_id'))) {
                throw new RuntimeException('Institution ownership cannot be changed.');
            }
        });
        static::deleting(function ($model) {
            if ($model->usesSharedInstitutionConnection() && $model->tenant_id !== app(TenantState::class)->id()) {
                throw new RuntimeException('Invalid institution ownership.');
            }
        });
    }

    protected function usesSharedInstitutionConnection(): bool
    {
        $connection = $this->getConnectionName() ?: DB::getDefaultConnection();

        if (in_array($connection, [config('tenancy.tenant_connection'), config('tenancy.central_connection')], true)) {
            return true;
        }
        $candidate = config('database.connections.'.$connection, []);
        $shared = config('database.connections.'.config('tenancy.central_connection'), []);

        return ! empty($shared['database']) && ($candidate['database'] ?? null) === $shared['database']
            && ($candidate['driver'] ?? null) === ($shared['driver'] ?? null)
            && ($candidate['host'] ?? null) === ($shared['host'] ?? null)
            && ($candidate['port'] ?? null) === ($shared['port'] ?? null);
    }
}
