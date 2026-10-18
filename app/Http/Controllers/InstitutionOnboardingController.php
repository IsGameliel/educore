<?php

namespace App\Http\Controllers;

use App\Models\Central\Institution;
use App\Services\Tenancy\InstitutionOnboarding;
use App\Services\Tenancy\TenantState;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstitutionOnboardingController extends Controller
{
    private function institution(): Institution
    {
        if (! Auth::guard('institution_account')->check()) {
            throw new HttpResponseException(redirect()->route('subscription.login'));
        }

        return Institution::findOrFail(Auth::guard('institution_account')->user()->institution_id);
    }

    public function show(InstitutionOnboarding $onboarding)
    {
        $institution = $this->institution();
        if (! $onboarding->available($institution)) {
            return redirect()->route('subscription.show')->with('status', 'Your school portal requires a verified, unexpired subscription and an active institution.');
        }
        if (! $onboarding->required($institution)) {
            return $this->enter($onboarding);
        }

        return response()->view('institution.onboarding', ['institution' => $institution])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, InstitutionOnboarding $onboarding)
    {
        $institution = $this->institution();
        abort_unless($onboarding->available($institution), 403);
        if ($onboarding->required($institution)) {
            $data = $request->validate(['name' => 'required|string|max:255',
                'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'logo' => [empty($institution->settings['logo_path']) ? 'required' : 'nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048']]);
            $actor = Auth::guard('institution_account')->id();
            $onboarding->complete($institution, $data, $request->file('logo'), $actor);
        }

        return $this->enter($onboarding);
    }

    public function enter(InstitutionOnboarding $onboarding)
    {
        $institution = $this->institution();
        abort_unless($onboarding->available($institution), 403);
        if ($onboarding->required($institution)) {
            return redirect()->route('subscription.onboarding');
        }
        if ($institution->settings['domain_pending_verification'] ?? false) {
            return response()->view('institution.domain-pending', ['institution' => $institution])->header('Cache-Control', 'private, no-store');
        }
        $token = $onboarding->issue($institution);

        return response()->view('institution.portal-handoff', ['institution' => $institution,
            'action' => $onboarding->origin($institution).'/institution/enter', 'token' => $token])
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function accept(Request $request, InstitutionOnboarding $onboarding)
    {
        // This cross-host POST uses a short-lived, single-use secret instead of a shared CSRF/session cookie.
        abort_unless($request->header('Origin') === rtrim(config('tenancy.platform_url'), '/'), 403);
        $request->validate(['token' => 'required|string|size:64']);
        $institution = Institution::findOrFail(app(TenantState::class)->id());
        $admin = $onboarding->consume($institution, $request->input('token'));
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($admin->two_factor_secret && $admin->two_factor_confirmed_at) {
            $request->session()->put(['login.id' => $admin->id, 'login.remember' => false]);

            return redirect()->route('two-factor.login');
        }
        Auth::guard('web')->login($admin);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        return redirect()->route('dashboard');
    }
}
