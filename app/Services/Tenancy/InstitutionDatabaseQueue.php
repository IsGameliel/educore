<?php

namespace App\Services\Tenancy;

use Illuminate\Queue\DatabaseQueue;

class InstitutionDatabaseQueue extends DatabaseQueue
{
    public function getQueue($queue)
    {
        return $queue ?: (app(TenantState::class)->id() ? 'institution_'.app(TenantState::class)->id() : parent::getQueue($queue));
    }

    protected function buildDatabaseRecord($queue, $payload, $availableAt, $attempts = 0)
    {
        $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $id = $data['institution_id'] ?? null;
        if (! is_string($id) || $queue !== 'institution_'.$id || (app(TenantState::class)->id() && app(TenantState::class)->id() !== $id)) {
            throw new \RuntimeException('Institution queue identity mismatch.');
        }
        if ($this->database->table($this->table)->where('tenant_id', $id)->count() >= config('tenancy.operations.max_jobs', 1000)) {
            throw new \RuntimeException('Institution queue quota exceeded.');
        }

        return [...parent::buildDatabaseRecord($queue, $payload, $availableAt, $attempts), 'tenant_id' => $id];
    }
}
