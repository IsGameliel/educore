<?php

namespace App\Services\Tenancy\Database;

use Illuminate\Database\SQLiteConnection;

class TenantSqliteConnection extends SQLiteConnection
{
    public function query()
    {
        return new TenantQueryBuilder($this, $this->getQueryGrammar(), $this->getPostProcessor());
    }

    protected function getDefaultQueryGrammar()
    {
        return new TenantSqliteGrammar($this);
    }
}
