<?php

namespace App\Services\Tenancy\Database;

use Illuminate\Database\Query\Grammars\SQLiteGrammar;

class TenantSqliteGrammar extends SQLiteGrammar
{
    use ScopesTenantSql;
}
