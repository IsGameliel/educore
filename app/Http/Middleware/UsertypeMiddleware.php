<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UsertypeMiddleware
{
    public function handle(Request $request, Closure $next, ...$usertypes): Response
    {
        if (!Auth::check() || !in_array(Auth::user()->usertype, $usertypes)) {
            abort(403, 'Unauthorized');
        }
        return $next($request);
    }
}