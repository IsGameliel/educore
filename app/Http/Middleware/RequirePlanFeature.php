<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\FeaturePermissions;
use Closure;
use Illuminate\Http\Request;

class RequirePlanFeature
{
    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();
        $permissions = app(FeaturePermissions::class);
        $action = explode('@', $route?->getActionName() ?? '');
        $feature = $permissions->forRoute($route?->getName(), $action[0], $action[1] ?? null);
        $financialWebhook = $request->isMethod('POST') && $request->path() === 'payments/paystack/webhook';
        if ($feature && ! $financialWebhook) {
            $permissions->authorize($feature);
        }

        return $next($request);
    }
}
