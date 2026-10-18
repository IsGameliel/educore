<?php

namespace App\Http\Middleware;

use App\Models\Central\Institution;
use App\Models\Central\InstitutionDomain;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResolveInstitution
{
    public function handle(Request $request, Closure $next)
    {
        // Redirector retains its session instance; rebuild it when host-scoped sessions change.
        app()->forgetInstance('redirect');
        $platformPath = $request->is('platform', 'platform/*', 'dashboard', 'dashboard/*')
            && ! $request->is('dashboard/widgets', 'dashboard/widgets/*');
        if (! config('tenancy.enabled') || in_array($request->getHost(), config('tenancy.legacy_hosts'), true)) {
            abort_if(config('tenancy.enabled') && ($platformPath || $request->is('institution/*', 'tenant-assets/*', 'subscribe', 'subscribe/*')), 404);

            return $next($request);
        }
        app(\App\Services\Tenancy\PlatformMaintenance::class)->ensureAvailable();
        if (in_array($request->getHost(), config('tenancy.central_domains'), true)) {
            abort_unless($platformPath || $request->is('subscribe', 'subscribe/*', 'pricing', 'faq', 'resources', 'support', 'up') || $request->path() === '/', 404);
            $keys = ['session.cookie', 'session.domain', 'session.connection', 'session.table', 'session.secure', 'auth.defaults.guard', 'cache.default', 'cache.stores.platform'];
            $previous = array_combine($keys, array_map(fn ($key) => config($key), $keys));
            $previousCache = app('cache')->store();
            config(['session.cookie' => 'educore_platform_session', 'session.domain' => null,
                'session.connection' => config('tenancy.central_connection'), 'session.table' => 'platform_sessions',
                'session.secure' => app()->environment('local', 'testing') ? $request->isSecure() : config('session.secure', true), 'auth.defaults.guard' => 'platform',
                'cache.default' => 'platform', 'cache.stores.platform' => ['driver' => app()->environment('testing') ? 'array' : 'file',
                    'path' => storage_path('framework/cache/platform'), 'lock_path' => storage_path('framework/cache/platform')]]);
            app(RateLimiter::class)->useCache(app('cache')->store());
            Auth::forgetGuards();
            app('session')->forgetDrivers();
            app()->forgetInstance('session.store');
            try {
                return $next($request);
            } finally {
                config($previous);
                app(RateLimiter::class)->useCache($previousCache);
                Auth::forgetGuards();
                app('session')->forgetDrivers();
                app()->forgetInstance('session.store');
            }
        }
        $domain = InstitutionDomain::where('domain', strtolower($request->getHost()))->first();
        abort_unless($domain, 404);
        $institution = Institution::findOrFail($domain->institution_id);
        abort_if($institution->settings['domain_pending_verification'] ?? false, 403, 'This school address is awaiting authorization, DNS and HTTPS verification by the platform administrator.');
        $financial = $request->isMethod('POST') && $request->path() === 'payments/paystack/webhook';
        abort_unless($institution->status === 'ready' || ($financial && in_array($institution->status, ['suspended','archived'], true)), 403, 'This institution is currently unavailable.');
        abort_if(! $financial && $institution->subscription_status === 'awaiting_payment', 403, 'Complete your institution subscription payment on the central website before signing in.');
        abort_unless($financial || app(\App\Services\Tenancy\SemesterBilling::class)->accessible($institution), 403, 'Your subscription is unpaid or has expired. Sign in to your institution billing account on the central website to renew.');
        // Shared database dumps, destructive restores and server-wide tooling are platform operations.
        abort_if($platformPath || $request->is('admin/backups', 'admin/backups/*', 'subscribe', 'subscribe/*', 'api/firebase/*', 'storage/*'), 403);
        $request->attributes->set('institution', $institution);

        return app(TenantContext::class)->run($institution, fn () => $next($request), financial: $financial);
    }
}
