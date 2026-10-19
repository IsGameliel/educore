<?php

namespace App\Services\Tenancy\Database;

use App\Services\Tenancy\FeaturePermissions;
use App\Services\Tenancy\InstitutionDatabases;
use App\Services\Tenancy\TenantSchema;
use App\Services\Tenancy\TenantState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TenantQueryBuilder extends Builder
{
    private function ownedValues(array $values, bool $insert): array
    {
        [$table] = app(TenantSchema::class)->table($this->from);
        $financialWrite = config('tenancy.financial_event', false) && in_array($table, ['result_appeals', 'transcript_requests'], true);
        if (! $financialWrite && ($feature = app(FeaturePermissions::class)->forTable($table))) {
            app(FeaturePermissions::class)->authorize($feature);
        }
        if (! app(TenantSchema::class)->owned($table)) {
            throw new RuntimeException('Platform tables cannot be modified through a tenant connection.');
        }
        $id = app(TenantState::class)->id() ?? throw new RuntimeException('Tenant identity is required.');
        if (array_key_exists('tenant_id', $values) && $values['tenant_id'] !== $id) {
            throw ValidationException::withMessages(['tenant_id' => 'Institution ownership cannot be changed.']);
        }
        if ($insert) {
            $values['tenant_id'] = $id;
        }
        if (isset($values['id']) && ! in_array($table, ['institution_sessions', 'sessions', 'job_batches'], true)
            && ! app(InstitutionDatabases::class)->primary($id)
            && DB::connection(config('tenancy.central_connection'))->table($table)
                ->where('id', $values['id'])->where('tenant_id', '!=', $id)->exists()) {
            throw ValidationException::withMessages(['id' => 'This identifier belongs to another institution.']);
        }
        foreach (app(TenantSchema::class)->foreignKeys($table) as $key) {
            if (count($key['columns']) !== 1 || ! app(TenantSchema::class)->owned($key['foreign_table'])) {
                continue;
            }
            $column = $key['columns'][0];
            if (! array_key_exists($column, $values) || $values[$column] === null) {
                continue;
            }
            if (is_object($values[$column]) || ! $this->connection->table($key['foreign_table'])->where($key['foreign_columns'][0], $values[$column])->exists()) {
                throw ValidationException::withMessages([$column => 'The selected record does not belong to this institution.']);
            }
        }
        // Polymorphic payments/tokens also need ownership checks.
        foreach ([['payable_type', 'payable_id'], ['tokenable_type', 'tokenable_id']] as [$type, $key]) {
            if (isset($values[$type], $values[$key])) {
                $class = Relation::getMorphedModel($values[$type]) ?: $values[$type];
                if (! is_subclass_of($class, Model::class) || ! $class::whereKey($values[$key])->exists()) {
                    throw ValidationException::withMessages([$key => 'The linked record does not belong to this institution.']);
                }
            }
        }

        return $values;
    }

    private function rows(array $values): array
    {
        if (! $values) {
            return [];
        }
        if (! is_array(reset($values))) {
            return $this->ownedValues($values, true);
        }

        return array_map(fn ($row) => $this->ownedValues($row, true), $values);
    }

    public function insert(array $values)
    {
        return parent::insert($this->rows($values));
    }

    public function insertOrIgnore(array $values)
    {
        return parent::insertOrIgnore($this->rows($values));
    }

    public function insertGetId(array $values, $sequence = null)
    {
        return parent::insertGetId($this->ownedValues($values, true), $sequence);
    }

    public function update(array $values)
    {
        return parent::update($this->ownedValues($values, false));
    }

    public function upsert(array $values, array|string $uniqueBy, ?array $update = null)
    {
        $uniqueBy = array_unique(['tenant_id', ...(array) $uniqueBy]);
        if ($update && in_array('tenant_id', $update, true)) {
            throw new RuntimeException('Upserts cannot change ownership.');
        }

        return parent::upsert($this->rows($values), $uniqueBy, $update);
    }

    public function delete($id = null)
    {
        [$table] = app(TenantSchema::class)->table($this->from);
        if ($feature = app(FeaturePermissions::class)->forTable($table)) {
            app(FeaturePermissions::class)->authorize($feature);
        }

        return parent::delete($id);
    }

    public function truncate()
    {
        throw new RuntimeException('Tenant tables cannot be truncated.');
    }

    public function insertUsing(array $columns, $query)
    {
        throw new RuntimeException('Use validated tenant-owned inserts.');
    }

    public function insertOrIgnoreUsing(array $columns, $query)
    {
        throw new RuntimeException('Use validated tenant-owned inserts.');
    }
}
