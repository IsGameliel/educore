<?php

use App\Models\Central\PlatformAdministrator;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Local-only MySQL migration rehearsal. Uses and removes one uniquely named disposable database.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! $app->environment('local', 'testing')) {
    throw new RuntimeException('Local/test environments only.');
}
$name = 'educore_security_rehearsal_'.bin2hex(random_bytes(5));
if (! preg_match('/^educore_security_rehearsal_[a-f0-9]{10}$/D', $name)) {
    throw new RuntimeException('Unsafe rehearsal name.');
}
$original = config('database.connections.central');
$default = DB::getDefaultConnection();
$created = false;
$checks = 0;
$check = function ($condition, $label) use (&$checks) {
    if (! $condition) {
        throw new RuntimeException($label.' '.Artisan::output());
    }
    $checks++;
};
try {
    config(['database.connections.rehearsal_server' => [...$original, 'database' => null, 'url' => null]]);
    $server = DB::connection('rehearsal_server');
    $server->statement('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    config(['database.connections.central' => [...$original, 'database' => $name, 'url' => null],
        'database.connections.tenant' => [...$original, 'database' => '__tenant_context_required__', 'url' => null],
        'mail.default' => 'array', 'queue.default' => 'sync', 'cache.default' => 'array']);
    DB::purge('central');
    DB::purge('tenant');
    $check(Artisan::call('tenancy:install', ['--no-interaction' => true]) === 0, 'Fresh MySQL installation');
    $db = DB::connection('central');
    foreach (['platform_login_sessions', 'platform_account_tokens', 'platform_audit_events', 'institution_portal_handoffs'] as $table) {
        $check($db->getSchemaBuilder()->hasTable($table) && ! $db->getSchemaBuilder()->hasColumn($table, 'tenant_id'), 'Central table isolation '.$table);
    }
    $admin = PlatformAdministrator::create(['name' => 'Rehearsal', 'email' => 'rehearsal@example.test', 'password' => 'Rehearsal-password-123']);
    $admin->refresh();
    $check($admin->role === 'read_only', 'Least privilege default');
    $admin->forceFill(['role' => 'support'])->save();
    $check(Artisan::call('tenancy:install', ['--no-interaction' => true]) === 0, 'Repeat installation');
    $check($admin->fresh()->role === 'support', 'Repeat installation preserves assigned roles');
    DB::setDefaultConnection('central');
    $migration = require __DIR__.'/../../database/central-migrations/2026_10_19_000000_add_platform_security.php';
    $migration->down();
    $migration->up();
    $admin = $admin->fresh();
    $check($admin->role === 'owner', 'Pre-upgrade administrator authority preserved');
    $check(Hash::check('Rehearsal-password-123', $admin->password), 'Existing credential hash preserved');
    $check($db->table('platform_audit_events')->count() === 0, 'No invented historical audit');
    echo json_encode(['database' => $name, 'checks_passed' => $checks, 'result' => 'success'], JSON_PRETTY_PRINT).PHP_EOL;
} finally {
    DB::setDefaultConnection($default);
    DB::purge('central');
    DB::purge('tenant');
    if ($created) {
        $server->statement('DROP DATABASE `'.$name.'`');
        echo 'Disposable rehearsal database removed.'.PHP_EOL;
    }
    DB::purge('rehearsal_server');
}
