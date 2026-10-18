<?php

namespace App\Services\Tenancy;

use App\Models\Central\PlatformAdministrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class PlatformSecurity
{
    public static function db()
    {
        return DB::connection(config('tenancy.central_connection'));
    }

    public static function login(Request $request, PlatformAdministrator $admin): void
    {
        Auth::guard('platform')->login($admin);
        $request->session()->regenerate();
        $key = Str::random(64);
        $request->session()->put('platform_login_key', $key);
        $request->session()->forget(['platform_pending_admin', 'platform_pending_at', 'platform_pending_version', 'platform_confirmed_at', 'platform_confirm_return', 'platform_setup_secret', 'platform_setup_at', 'recovery_codes']);
        self::db()->table('platform_login_sessions')->insert(['id' => $key, 'administrator_id' => $admin->id,
            'security_version' => $admin->security_version, 'ip_address' => $request->ip(),
            'user_agent' => mb_substr($request->userAgent() ?? '', 0, 500), 'last_seen_at' => now()]);
        PlatformAudit::record('security.login', (string) $admin->id, [], $admin->id);
    }

    public static function revoke(PlatformAdministrator $admin): void
    {
        $admin->increment('security_version');
        self::db()->table('platform_login_sessions')->where('administrator_id', $admin->id)->delete();
    }

    public static function password(Request $request): void
    {
        $request->validate(['current_password' => 'required|string']);
        if (! Hash::check($request->input('current_password'), Auth::guard('platform')->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
        }
    }

    public static function verifyFactor(PlatformAdministrator $admin, string $code): bool
    {
        if (! $admin->two_factor_confirmed_at) {
            return false;
        }

        // Lock the account so a recovery code cannot be consumed concurrently.
        return self::db()->transaction(function () use ($admin, $code) {
            $admin = PlatformAdministrator::lockForUpdate()->findOrFail($admin->id);
            foreach ($admin->two_factor_recovery_codes ?? [] as $index => $hash) {
                if (Hash::check($code, $hash)) {
                    $codes = $admin->two_factor_recovery_codes;
                    unset($codes[$index]);
                    $admin->two_factor_recovery_codes = array_values($codes);
                    $admin->save();
                    PlatformAudit::record('security.recovery_code_used', (string) $admin->id, [], $admin->id);

                    return true;
                }
            }

            return preg_match('/^\d{6}$/D', $code) && app(TwoFactorAuthenticationProvider::class)->verify($admin->two_factor_secret, $code);
        });
    }
}
