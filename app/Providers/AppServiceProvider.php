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
        \App\Models\User::observe(\App\Observers\StudentBillingObserver::class);
        foreach ([\App\Models\ResultAppeal::class, \App\Models\TranscriptRequest::class, \App\Models\TranscriptDocument::class] as $model) {
            $model::observe(\App\Observers\StudentServiceObserver::class);
        }
        \Illuminate\Support\Facades\Queue::looping(function ($event) {
            static $lastHeartbeat = 0;
            $queueName = config('queue.connections.'.config('queue.default').'.queue', 'default');
            $observedQueues = array_map('trim', explode(',', (string) $event->queue));
            if ($event->connectionName === config('queue.default') && in_array($queueName, $observedQueues, true) && time() - $lastHeartbeat >= 30) {
                \App\Services\OperationsHealth::record('queue', 'success', 'Worker heartbeat received.');
                $lastHeartbeat = time();
            }
        });
        \Illuminate\Support\Facades\Queue::failing(function ($event) {
            if ($event->connectionName === config('queue.default')) {
                \App\Services\OperationsHealth::record('queue', 'failed', 'A queued job failed. Review failed jobs before retrying.');
            }
        });
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
