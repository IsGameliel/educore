<?php

use App\Models\Central\PlatformAdministrator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->previousMigrationEnvironment = [getenv('TENANCY_MIGRATION_USERNAME'), getenv('TENANCY_MIGRATION_PASSWORD')];
    putenv('TENANCY_MIGRATION_USERNAME');
    putenv('TENANCY_MIGRATION_PASSWORD');
    $this->bootstrapRoot = storage_path('framework/testing/bootstrap-'.Str::uuid());
    File::ensureDirectoryExists($this->bootstrapRoot);
    touch($this->bootstrapRoot.'/fresh.sqlite');
    config(['database.connections.central' => [...config('database.connections.sqlite'),
        'database' => $this->bootstrapRoot.'/fresh.sqlite', 'url' => null],
        'tenancy.migration_username' => null, 'tenancy.migration_password' => null,
        'tenancy.upgrading' => null]);
    DB::purge('central');
    putenv('CPANEL_TEST_OWNER_PASSWORD=Fresh-platform-password-123');
    $this->bootstrapOptions = ['--empty-database' => true, '--admin-email' => 'owner@example.test',
        '--admin-name' => 'Platform Owner', '--password-env' => 'CPANEL_TEST_OWNER_PASSWORD', '--no-interaction' => true];
});

afterEach(function () {
    foreach (['TENANCY_MIGRATION_USERNAME', 'TENANCY_MIGRATION_PASSWORD'] as $index => $variable) {
        $value = $this->previousMigrationEnvironment[$index];
        putenv($value === false ? $variable : $variable.'='.$value);
    }
    putenv('CPANEL_TEST_OWNER_PASSWORD');
    DB::purge('central');
    File::deleteDirectory($this->bootstrapRoot);
});

it('bootstraps an empty shared schema with an owner and no institutions', function () {
    $this->app->instance('env', 'production');
    // Cached configuration stays credential-free; the operator supplies CLI-only credentials.
    putenv('TENANCY_MIGRATION_USERNAME=disposable_migrator');
    putenv('TENANCY_MIGRATION_PASSWORD=disposable-password');
    expect(Artisan::call('tenancy:bootstrap', $this->bootstrapOptions))->toBe(0);
    $owner = PlatformAdministrator::firstOrFail();
    expect($owner->email)->toBe('owner@example.test')->and($owner->role)->toBe('owner')
        ->and(Hash::check('Fresh-platform-password-123', $owner->password))->toBeTrue()
        ->and(DB::connection('central')->table('institutions')->count())->toBe(0)
        ->and(DB::connection('central')->table('plans')->where('signup_enabled', true)->count())->toBe(0)
        ->and(DB::connection('central')->getSchemaBuilder()->hasTable('institution_portal_handoffs'))->toBeTrue()
        ->and(config('tenancy.upgrading'))->toBeNull()
        ->and(getenv('CPANEL_TEST_OWNER_PASSWORD'))->toBe('Fresh-platform-password-123');
    expect(Artisan::output())->not->toContain('Fresh-platform-password-123');
    expect(Artisan::call('tenancy:bootstrap', $this->bootstrapOptions))->toBe(1);
    expect(Artisan::output())->toContain('not empty');
    expect(PlatformAdministrator::count())->toBe(1);
});

it('refuses a foreign database before making schema changes', function () {
    DB::connection('central')->statement('CREATE TABLE unrelated_data (id INTEGER PRIMARY KEY)');
    DB::connection('central')->table('unrelated_data')->insert(['id' => 7]);
    expect(Artisan::call('tenancy:bootstrap', $this->bootstrapOptions))->toBe(1);
    expect(DB::connection('central')->table('unrelated_data')->value('id'))->toBe(7)
        ->and(DB::connection('central')->getSchemaBuilder()->hasTable('institutions'))->toBeFalse();
});

it('requires explicit empty-target acknowledgement and valid owner credentials', function () {
    expect(Artisan::call('tenancy:bootstrap', [...$this->bootstrapOptions, '--empty-database' => false]))->toBe(1);
    expect(Artisan::call('tenancy:bootstrap', [...$this->bootstrapOptions, '--admin-email' => 'invalid']))->toBe(1);
    expect(DB::connection('central')->getSchemaBuilder()->hasTable('institutions'))->toBeFalse();
});

it('requires production migration credentials without persisting credential overrides', function () {
    $this->app->instance('env', 'production');
    $before = config('database.connections.central');
    expect(Artisan::call('tenancy:bootstrap', $this->bootstrapOptions))->toBe(1);
    expect(Artisan::output())->toContain('dedicated TENANCY_MIGRATION_USERNAME');
    expect(config('database.connections.central'))->toBe($before)
        ->and(config('tenancy.upgrading'))->toBeNull()
        ->and(DB::connection('central')->getSchemaBuilder()->hasTable('institutions'))->toBeFalse();
});
