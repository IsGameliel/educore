<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventAuthPageCaching
{
    /**
     * Prevent browsers and proxies from reusing stale forms with old CSRF tokens.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || ! $this->isAuthPage($request)) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');

        return $response;
    }

    private function isAuthPage(Request $request): bool
    {
        return $request->routeIs(
            'login',
            'register',
            'password.request',
            'password.reset',
            'verification.notice',
            'two-factor.login',
        );
    }
}
