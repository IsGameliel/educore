<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Department;
use App\Support\StudentUpdateFeed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('profile.update-profile-information-form', function ($view) {
            $view->with('departments', Department::all());
        });

        View::composer('partials.top', function ($view) {
            $user = Auth::user();
            $updates = $user ? StudentUpdateFeed::forUser($user, 5) : collect();

            $view->with([
                'studentUpdateNotifications' => $updates,
                'studentUpdateNotificationCount' => $updates->count(),
            ]);
        });
    }
}
