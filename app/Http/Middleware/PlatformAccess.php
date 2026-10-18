<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\PlatformAudit;
use App\Services\Tenancy\PlatformSecurity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlatformAccess
{
    public function handle(Request $request, Closure $next)
    {
        $admin = Auth::guard('platform')->user();
        if (! $admin) {
            return redirect()->route('platform.login');
        }
        $db = DB::connection(config('tenancy.central_connection'));
        $sessionKey = $request->session()->get('platform_login_key');
        if (! $admin->is_active || ! $sessionKey || ! $db->table('platform_login_sessions')
            ->where('id', $sessionKey)->where('administrator_id', $admin->id)->where('security_version', $admin->security_version)->exists()) {
            Auth::guard('platform')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('platform.login')->withErrors(['email' => 'Your session has ended. Please sign in again.']);
        }
        $name = $request->route()->getName();
        if (! $admin->canAccessPlatformRoute($name)) {
            PlatformAudit::record('security.access_denied', $name);
            abort(403);
        }
        $db->table('platform_login_sessions')->where('id', $sessionKey)->update(['last_seen_at' => now()]);
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD') && ! in_array($name, ['platform.confirm', 'platform.logout'], true)
            && (int) $request->session()->get('platform_confirmed_at', 0) < now()->subMinutes(10)->timestamp) {
            if ($request->filled('current_password')) {
                PlatformSecurity::password($request);
                $request->session()->put('platform_confirmed_at', now()->timestamp);
            } else {
                $return = match ($name) {
                    'platform.plans.store' => route('platform.plans.create'),
                    'platform.plans.update' => route('platform.plans.edit', $request->route('plan')),
                    'platform.institutions.status', 'platform.institutions.domain', 'platform.institutions.features', 'platform.institutions.copy.save', 'platform.institutions.copy.sync', 'platform.institutions.copy.pause' => route('platform.institutions.show', $request->route('institution')),
                    'platform.administrators.invite', 'platform.administrators.update', 'platform.administrators.resend' => route('platform.administrators.index'),
                    default => route('platform.account.show'),
                };
                $request->session()->put('platform_confirm_return', $return);

                return redirect()->route('platform.confirm.form')->with('status', 'Confirm your password, then repeat the action.');
            }
        }

        return $next($request);
    }
}
