<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

class RequirePlatformDomain
{
    public function handle(Request $request, \Closure $next)
    {
        abort_unless(config('tenancy.enabled') && in_array($request->getHost(), config('tenancy.central_domains'), true), 404);

        return $next($request);
    }
}
