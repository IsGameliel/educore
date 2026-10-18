<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\Laravel\Fortify\Contracts\LoginResponse::class, \App\Http\Responses\LoginResponse::class);
        $this->app->singleton(\Laravel\Fortify\Contracts\TwoFactorLoginResponse::class, \App\Http\Responses\TwoFactorLoginResponse::class);
        $this->app->singleton(\Laravel\Fortify\Contracts\RegisterResponse::class, \App\Http\Responses\RegisterResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('platform-sensitive', function (Request $request) {
            return Limit::perMinute(5)->by('platform|'.$request->route()->getName().'|'.($request->session()->get('platform_pending_admin') ?? auth('platform')->id() ?? 'guest').'|'.$request->ip());
        });
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = (app(\App\Services\Tenancy\TenantState::class)->id() ?? 'legacy').'|'.Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by((app(\App\Services\Tenancy\TenantState::class)->id() ?? 'legacy').'|'.$request->session()->get('login.id'));
        });
    }
}
