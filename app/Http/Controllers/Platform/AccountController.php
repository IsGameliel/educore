<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\PlatformAdministrator;
use App\Services\Tenancy\PlatformAudit;
use App\Services\Tenancy\PlatformSecurity as Security;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class AccountController extends Controller
{
    public function show(Request $request)
    {
        return response()->view('platform.account', ['admin' => Auth::guard('platform')->user(),
            'setupSecret' => $request->session()->has('platform_setup_secret') && $request->session()->get('platform_setup_at', 0) > now()->subMinutes(10)->timestamp ? Crypt::decryptString($request->session()->get('platform_setup_secret')) : null,
            'recoveryCodes' => $request->session()->has('recovery_codes') ? json_decode(Crypt::decryptString($request->session()->get('recovery_codes')), true) : [],
            'sessions' => Security::db()->table('platform_login_sessions')->where('administrator_id', Auth::guard('platform')->id())->orderByDesc('last_seen_at')->get(),
            'currentKey' => $request->session()->get('platform_login_key')])->header('Cache-Control', 'no-store, private');
    }

    public function confirmForm()
    {
        return view('platform.security-form', ['mode' => 'confirm']);
    }

    public function confirm(Request $request)
    {
        Security::password($request);
        $request->session()->put('platform_confirmed_at', now()->timestamp);

        return redirect()->to($request->session()->pull('platform_confirm_return', route('platform.account.show')))->with('status', 'Password confirmed for ten minutes. You can now repeat your action.');
    }

    public function password(Request $request)
    {
        Security::password($request);
        $data = $request->validate(['password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()]]);
        $admin = Auth::guard('platform')->user();
        Security::db()->transaction(function () use ($admin, $data) {
            $admin->password = $data['password'];
            $admin->save();
            Security::revoke($admin);
            PlatformAudit::record('security.password_changed', (string) $admin->id);
        });
        Security::login($request, $admin->fresh());

        return back()->with('status', 'Password changed. Other sessions have been revoked.');
    }

    public function revoke(Request $request, string $session)
    {
        Security::password($request);
        abort_if($session === $request->session()->get('platform_login_key'), 422, 'Use Sign out to end this session.');
        $query = Security::db()->table('platform_login_sessions')->where('administrator_id', Auth::guard('platform')->id())->where('id', $session);
        abort_unless($query->exists(), 404);
        $query->delete();
        PlatformAudit::record('security.session_revoked', (string) Auth::guard('platform')->id());

        return back()->with('status', 'Session revoked.');
    }

    public function setup(Request $request)
    {
        Security::password($request);
        $admin = Auth::guard('platform')->user();
        abort_if($admin->two_factor_confirmed_at, 422, 'Two-factor authentication is already enabled.');
        $secret = app(TwoFactorAuthenticationProvider::class)->generateSecretKey();
        $request->session()->put(['platform_setup_secret' => Crypt::encryptString($secret), 'platform_setup_at' => now()->timestamp]);

        return back()->with('status', 'Add the setup key to your authenticator, then confirm a code.');
    }

    public function enable(Request $request)
    {
        Security::password($request);
        $request->validate(['code' => 'required|digits:6']);
        $encrypted = $request->session()->get('platform_setup_secret');
        abort_unless($encrypted && $request->session()->get('platform_setup_at', 0) > now()->subMinutes(10)->timestamp, 422, 'Start authenticator setup first. Setup expires after ten minutes.');
        $secret = Crypt::decryptString($encrypted);
        if (! app(TwoFactorAuthenticationProvider::class)->verify($secret, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'The authenticator code is invalid or already used.']);
        }
        $admin = Auth::guard('platform')->user();
        abort_if($admin->two_factor_confirmed_at, 422);
        $codes = collect(range(1, 8))->map(fn () => Str::random(20))->all();
        Security::db()->transaction(function () use ($admin, $secret, $codes) {
            $admin->two_factor_secret = $secret;
            $admin->two_factor_recovery_codes = array_map(fn ($code) => Hash::make($code), $codes);
            $admin->two_factor_confirmed_at = now();
            $admin->save();
            Security::revoke($admin);
            PlatformAudit::record('security.two_factor_enabled', (string) $admin->id);
        });
        Security::login($request, $admin->fresh());
        $request->session()->forget('platform_setup_secret');

        return back()->with('recovery_codes', Crypt::encryptString(json_encode($codes)))->with('status', 'Two-factor authentication enabled. Save these one-use recovery codes now.');
    }

    public function disable(Request $request)
    {
        Security::password($request);
        $request->validate(['code' => 'required|string|max:100']);
        $admin = Auth::guard('platform')->user();
        if (! Security::verifyFactor($admin, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'Enter a valid authenticator or recovery code.']);
        }
        Security::db()->transaction(function () use ($admin) {
            $admin->two_factor_secret = null;
            $admin->two_factor_recovery_codes = null;
            $admin->two_factor_confirmed_at = null;
            $admin->save();
            Security::revoke($admin);
            PlatformAudit::record('security.two_factor_disabled', (string) $admin->id);
        });
        Security::login($request, $admin->fresh());

        return back()->with('status', 'Two-factor authentication disabled. Other sessions were revoked.');
    }

    public function challengeForm(Request $request)
    {
        abort_unless($request->session()->has('platform_pending_admin'), 403);

        return view('platform.security-form', ['mode' => 'challenge']);
    }

    public function challenge(Request $request)
    {
        $request->validate(['code' => 'required|string|max:100']);
        $admin = PlatformAdministrator::find($request->session()->get('platform_pending_admin'));
        abort_unless($admin && $admin->is_active && $request->session()->get('platform_pending_at', 0) >= now()->subMinutes(5)->timestamp
            && $request->session()->get('platform_pending_version') === $admin->security_version, 403);
        if (! Security::verifyFactor($admin, $request->input('code'))) {
            PlatformAudit::record('security.two_factor_failed', (string) $admin->id, [], $admin->id);
            throw ValidationException::withMessages(['code' => 'The authenticator or recovery code is invalid.']);
        }
        Security::login($request, $admin);

        return redirect()->route('platform.index');
    }

    public function forgot()
    {
        return view('platform.security-form', ['mode' => 'forgot']);
    }

    public function sendReset(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        $admin = PlatformAdministrator::where('email', strtolower($data['email']))->where('is_active', true)->first();
        if ($admin) {
            self::sendToken($admin, 'reset');
        }

        return back()->with('status', 'If this is an active platform account, a recovery link will be sent.');
    }

    public static function sendToken(PlatformAdministrator $admin, string $purpose): void
    {
        $token = Str::random(64);
        Security::db()->table('platform_account_tokens')->updateOrInsert(['email' => $admin->email], [
            'token' => hash('sha256', $token), 'purpose' => $purpose,
            'expires_at' => now()->addMinutes($purpose === 'invite' ? 1440 : 60), 'created_at' => now(),
        ]);
        $url = rtrim(config('tenancy.platform_url'), '/').'/dashboard/reset-password/'.$token.'?email='.urlencode($admin->email);
        Mail::raw("Set your Educore platform password: {$url}\nThis link expires ".($purpose === 'invite' ? 'in 24 hours.' : 'in one hour.').' If you did not request it, ignore this message.', fn ($mail) => $mail->to($admin->email)->subject($purpose === 'invite' ? 'Your platform administrator invitation' : 'Platform password recovery'));
        PlatformAudit::record($purpose === 'invite' ? 'administrator.invitation_sent' : 'security.recovery_requested', (string) $admin->id);
    }

    public function resetForm(Request $request, string $token)
    {
        return response()->view('platform.security-form', ['mode' => 'reset', 'token' => $token, 'email' => $request->query('email')])->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function reset(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'token' => 'required|string|max:100',
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()]]);
        Security::db()->transaction(function () use ($data) {
            $token = Security::db()->table('platform_account_tokens')->where('email', strtolower($data['email']))->lockForUpdate()->first();
            $admin = PlatformAdministrator::where('email', strtolower($data['email']))->lockForUpdate()->first();
            if (! $token || ! $admin || ! $admin->is_active || now()->gte($token->expires_at) || ! hash_equals($token->token, hash('sha256', $data['token']))) {
                throw ValidationException::withMessages(['email' => 'This link is invalid or expired. Request a new link.']);
            }
            $admin->password = $data['password'];
            $admin->save();
            Security::revoke($admin);
            Security::db()->table('platform_account_tokens')->where('email', $admin->email)->delete();
            PlatformAudit::record('security.password_reset', (string) $admin->id, ['purpose' => $token->purpose], $admin->id);
        });

        return redirect()->route('platform.login')->with('status', 'Password saved. Sign in to continue. Existing two-factor protection still applies.');
    }
}
