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
        $this->app->singleton(\App\Services\Tenancy\TenantState::class);
        $this->app->singleton(\App\Services\Tenancy\InstitutionMutex::class);
        $this->app->singleton(\App\Services\Tenancy\TenantSchema::class);
        $this->app->singleton(\App\Services\Tenancy\TenantQueueScope::class);
        \Illuminate\Database\Connection::resolverFor('mysql', fn ($pdo, $database, $prefix, $config) =>
            ($config['tenant_scoped'] ?? false)
                ? new \App\Services\Tenancy\Database\TenantMySqlConnection($pdo, $database, $prefix, $config)
                : new \Illuminate\Database\MySqlConnection($pdo, $database, $prefix, $config));
        \Illuminate\Database\Connection::resolverFor('sqlite', fn ($pdo, $database, $prefix, $config) =>
            ($config['tenant_scoped'] ?? false)
                ? new \App\Services\Tenancy\Database\TenantSqliteConnection($pdo, $database, $prefix, $config)
                : new \Illuminate\Database\SQLiteConnection($pdo, $database, $prefix, $config));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (['firebase.auth', 'firebase.database', 'firebase.firestore', 'firebase.messaging', 'firebase.storage'] as $service) {
            $this->app->beforeResolving($service, function () {
                if (app(\App\Services\Tenancy\TenantState::class)->id()) { throw new \RuntimeException('Shared Firebase services are unavailable in an institution context.'); }
            });
        }
        $cache = $this->app->make('cache');
        $this->app->instance(\Illuminate\Cache\RateLimiter::class, new \App\Services\Tenancy\TenantRateLimiter($cache->store(config('cache.limiter'))));
        \Illuminate\Support\Facades\Facade::clearResolvedInstance(\Illuminate\Cache\RateLimiter::class);
        \Laravel\Sanctum\Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);
        $this->app['queue']->addConnector('institution_database', fn () => new \App\Services\Tenancy\InstitutionQueueConnector($this->app['db']));
        \Illuminate\Queue\Queue::createPayloadUsing(function ($connection, $queue) {
            $id=app(\App\Services\Tenancy\TenantState::class)->id();
            if ($id && ! in_array($connection, ['institutions','sync'], true)) throw new \RuntimeException('Institution jobs must use the central institution queue connection.');
            if (! $id && is_string($queue) && preg_match('/\Ainstitution_([a-f0-9-]{36})\z/D',$queue,$match)) $id=$match[1];
            return $id ? ['institution_id'=>$id] : [];
        });
        $this->app['events']->listen(\Illuminate\Queue\Events\JobProcessing::class, fn ($event) => app(\App\Services\Tenancy\TenantQueueScope::class)->before($event));
        $this->app['events']->listen(\Illuminate\Queue\Events\JobAttempted::class, fn ($event) => app(\App\Services\Tenancy\TenantQueueScope::class)->after($event));
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
