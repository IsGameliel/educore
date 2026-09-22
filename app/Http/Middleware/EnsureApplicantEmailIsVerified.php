<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;

class EnsureApplicantEmailIsVerified extends EnsureEmailIsVerified
{
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        // Assigned staff do not take part in applicant onboarding.
        if ($request->user()?->isStaff()) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
