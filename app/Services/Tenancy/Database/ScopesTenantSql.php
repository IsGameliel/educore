<?php

namespace App\Services\Tenancy\Database;

use App\Services\Tenancy\TenantSchema;
use App\Services\Tenancy\TenantState;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use RuntimeException;

trait ScopesTenantSql
{
    public function compileWheres(Builder $query)
    {
        $sql = parent::compileWheres($query);
        $from = $query instanceof JoinClause ? $query->table : $query->from;
        if ($from === null) {
            return $sql;
        }
        [$table, $alias] = app(TenantSchema::class)->table($from);
        if (! app(TenantSchema::class)->owned($table)) {
            throw new RuntimeException('Platform tables cannot be queried through a tenant connection.');
        }
        $id = app(TenantState::class)->id();
        if (! $id || ! preg_match('/\A[0-9a-f-]{36}\z/i', $id)) {
            throw new RuntimeException('Tenant identity is required.');
        }
        // UUID is validated, not request input. No extra bindings are introduced into nested queries.
        $predicate = $this->wrap($alias.'.tenant_id')." = '".$id."'";
        $keyword = $query instanceof JoinClause ? 'on' : 'where';

        return $sql === '' ? $keyword.' '.$predicate : $keyword.' ('.substr($sql, strlen($keyword) + 1).') and '.$predicate;
    }
}
