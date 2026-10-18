<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\TenantState;
use Illuminate\Http\Request;

class RequireInstitution
{
    public function handle(Request $request, \Closure $next)
    {
        abort_unless(app(TenantState::class)->id(), 404);

        return $next($request);
    }
}
