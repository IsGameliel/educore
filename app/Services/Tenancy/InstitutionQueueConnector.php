<?php

namespace App\Services\Tenancy;

use Illuminate\Queue\Connectors\DatabaseConnector;

class InstitutionQueueConnector extends DatabaseConnector
{
    public function connect(array $config)
    {
        return new InstitutionDatabaseQueue($this->connections->connection(config('tenancy.central_connection')),
            $config['table'], $config['queue'], $config['retry_after'] ?? 180, $config['after_commit'] ?? true);
    }
}
