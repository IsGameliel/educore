<?php

namespace App\Services\Tenancy\Database;

use Illuminate\Database\MySqlConnection;

class TenantMySqlConnection extends MySqlConnection
{
    public function query()
    {
        return new TenantQueryBuilder($this, $this->getQueryGrammar(), $this->getPostProcessor());
    }

    protected function getDefaultQueryGrammar()
    {
        return new TenantMySqlGrammar($this);
    }
}
