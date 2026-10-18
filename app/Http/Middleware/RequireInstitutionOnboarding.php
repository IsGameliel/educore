<?php

namespace App\Http\Middleware;

use App\Models\Central\Institution;
use App\Services\Tenancy\InstitutionOnboarding;
use App\Services\Tenancy\TenantState;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequireInstitutionOnboarding
{
    public function handle(Request $request, Closure $next)
    {
        $id = app(TenantState::class)->id();
        if ($id && Auth::guard('web')->check() && ! $request->is('logout', 'institution/logo', 'payments/paystack/*', 'user/*', 'two-factor-challenge')) {
            $institution = Institution::findOrFail($id);
            if (app(InstitutionOnboarding::class)->required($institution)) {
                abort_unless(Auth::guard('web')->user()->usertype === 'admin', 403, 'Your school administrator must complete institution setup first.');

                return redirect()->away(rtrim(config('tenancy.platform_url'), '/').'/subscribe/onboarding');
            }
        }

        return $next($request);
    }
}
