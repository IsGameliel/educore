<?php

namespace App\Services\Tenancy\Database;

use Illuminate\Database\Query\Grammars\MySqlGrammar;

class TenantMySqlGrammar extends MySqlGrammar
{
    use ScopesTenantSql;
}
