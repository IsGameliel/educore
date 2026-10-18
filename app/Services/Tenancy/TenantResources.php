<?php

namespace App\Services\Tenancy;

use App\Models\Central\Institution;
use Illuminate\Cache\RateLimiter;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class TenantResources
{
    private array $previous = [];

    private array $previousLogContext = [];

    private string $timezone;

    private ?SessionManager $previousSessionManager = null;

    private ?Store $previousSessionStore = null;

    public function __construct()
    {
        $this->timezone = date_default_timezone_get();
    }

    public function enter(Institution $institution): void
    {
        $this->previousSessionManager = app('session');
        $this->previousSessionStore = app()->resolved('session.store') ? app('session.store') : null;
        $id = $institution->id;
        $root = config('tenancy.storage_root').'/'.$id;
        $domain = $institution->domains()->where('is_primary', true)->value('domain');
        $appUrl = parse_url(config('app.url'));
        $origin = request()->getHost() === $domain ? request()->getSchemeAndHttpHost()
            : ($appUrl['scheme'] ?? 'http').'://'.$domain.(isset($appUrl['port']) ? ':'.$appUrl['port'] : '');
        $store = 'institution_'.$id;
        $baseStore = config('cache.stores.'.config('cache.default'));
        if (! in_array($baseStore['driver'] ?? '', ['array', 'file', 'database'], true)) {
            $baseStore = config('cache.stores.database');
        }
        $baseStore['prefix'] = 'institution_'.$id.'_';
        $baseStore['path'] = $root.'/cache';
        $baseStore['lock_path'] = $root.'/cache';
        if (($baseStore['driver'] ?? '') === 'database') {
            $baseStore['connection'] = config('tenancy.tenant_connection');
            $baseStore['lock_connection'] = config('tenancy.tenant_connection');
        }
        $queueName = 'institution_'.$id;
        $values = [
            'app.name' => $institution->name, 'app.url' => $origin, 'app.timezone' => $institution->timezone,
            'session.cookie' => 'institution_'.$id.'_session', 'session.domain' => null,
            'session.secure' => app()->environment('local', 'testing') ? request()->isSecure() : config('session.secure', true),
            'session.connection' => config('tenancy.tenant_connection'), 'session.files' => $root.'/sessions',
            'session.table' => 'institution_sessions',
            'cache.default' => $store, 'cache.stores.'.$store => $baseStore,
            'auth.passwords.users.connection' => config('tenancy.tenant_connection'),
            'auth.passwords.users.table' => 'institution_password_resets',
            'queue.default' => config('queue.default') === 'sync' ? 'sync' : 'institutions',
            'queue.connections.institutions' => ['driver' => 'institution_database', 'connection' => config('tenancy.central_connection'),
                'table' => 'jobs', 'queue' => 'default', 'retry_after' => 180, 'after_commit' => true],
            'queue.failed.database' => config('tenancy.central_connection'),
            'queue.batching.database' => config('tenancy.central_connection'),
            'filesystems.disks.local' => ['driver' => 'local', 'root' => $root.'/private', 'serve' => false, 'throw' => true],
            'filesystems.disks.public' => ['driver' => 'local', 'root' => $root.'/public', 'url' => $origin.'/tenant-assets', 'visibility' => 'private', 'throw' => true],
            // Cloud assets are also isolated by prefix; public/local remain protected local roots.
            'filesystems.disks.s3.root' => 'institutions/'.$id,
            'services.paystack.secret_key' => app(InstitutionIntegrations::class)->key($institution->id),
            'mail.from.name' => $institution->name,
            'institution.branding' => ['name' => $institution->name, ...($institution->settings ?? [])],
        ];
        $this->timezone = date_default_timezone_get();
        foreach ($values as $key => $value) {
            $this->previous[$key] = config($key);
        }
        config($values);
        $this->previousLogContext = Log::sharedContext();
        Log::shareContext(['institution_id' => $institution->id]);
        date_default_timezone_set($institution->timezone);
        URL::forceRootUrl($origin);
        URL::defaults([]);
        // Provisioning may run inside a central HTTP request. Keep its active session
        // manager intact so StartSession saves the same store that owns the cookie.
        app()->instance('session', new SessionManager(app()));
        $this->resetManagers();
    }

    public function leave(): void
    {
        config($this->previous);
        Log::withoutContext(['institution_id']);
        Log::flushSharedContext();
        Log::shareContext($this->previousLogContext);
        date_default_timezone_set($this->timezone);
        URL::forceRootUrl(null);
        $this->resetManagers();
        if ($this->previousSessionManager) {
            app()->instance('session', $this->previousSessionManager);
            if ($this->previousSessionStore) {
                app()->instance('session.store', $this->previousSessionStore);
            }
            Facade::clearResolvedInstance('session');
        }
    }

    private function resetManagers(): void
    {
        // StartSession is a singleton and retains the manager injected for a previous host.
        app()->forgetInstance(StartSession::class);
        Storage::forgetDisk(['local', 'public', 's3']);
        Auth::forgetGuards();
        if (app()->bound('session')) {
            app('session')->forgetDrivers();
        }
        app()->forgetInstance('session.store');
        app()->forgetInstance('cache.store');
        app()->forgetInstance('auth.password');
        app(RateLimiter::class)->useCache(app('cache')->store());
        if (app()->bound('mail.manager') && method_exists(app('mail.manager'), 'forgetMailers')) {
            app('mail.manager')->forgetMailers();
        }
        foreach (['session', 'auth.password'] as $name) {
            Facade::clearResolvedInstance($name);
        }
    }
}
