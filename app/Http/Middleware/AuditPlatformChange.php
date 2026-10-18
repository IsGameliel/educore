<?php

namespace App\Http\Middleware;

use App\Models\Central\Institution;
use App\Models\Central\Plan;
use App\Services\Tenancy\PlatformAudit;
use App\Services\Tenancy\PlatformSecurity;
use Closure;
use Illuminate\Http\Request;

class AuditPlatformChange
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }
        $name = $request->route()->getName();
        $subject = $request->route('institution') ?? $request->route('plan') ?? $request->input('code');
        $snapshot = function () use ($name, $subject) {
            if (str_starts_with($name, 'platform.plans.')) {
                $plan = Plan::with('features')->find($subject);

                return $plan ? [...$plan->only(['code', 'name', 'standard_rate_minor', 'promotional_rate_minor', 'signup_enabled']), 'features' => $plan->features->pluck('code')->sort()->values()->all()] : null;
            }
            $school = Institution::with('domains')->find($subject);

            return $school ? [...$school->only(['status', 'plan_code']), 'domains' => $school->domains->pluck('domain')->all()] : null;
        };

        return PlatformSecurity::db()->transaction(function () use ($snapshot, $request, $next, $name, $subject) {
            $before = $snapshot();
            $response = $next($request);
            if ($response->getStatusCode() < 400) {
                PlatformAudit::record($name, (string) $subject, ['before' => $before, 'after' => $snapshot(),
                    'reason' => $request->input('reason'), 'feature_code' => $request->input('feature_code'),
                    'allowed' => $request->input('allowed'), 'expires_at' => $request->input('expires_at')]);
            }

            return $response;
        });
    }
}
