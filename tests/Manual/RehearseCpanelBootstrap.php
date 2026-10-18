<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('local', 'testing')) {
    throw new RuntimeException('Local/test rehearsal only.');
}
$original = config('database.connections.central');
if (($original['driver'] ?? null) !== 'mysql' || ! in_array($original['host'], ['127.0.0.1', 'localhost'], true)) {
    throw new RuntimeException('Requires local MySQL.');
}
$name = 'euvion_boot_'.bin2hex(random_bytes(5));
if (! preg_match('/\Aeuvion_boot_[a-f0-9]{10}\z/D', $name)) {
    throw new RuntimeException('Unsafe disposable name.');
}
$password = bin2hex(random_bytes(24));
$createdDatabase = $createdUser = false;
$checks = 0;
$check = static function (bool $condition, string $label) use (&$checks): void {
    if (! $condition) {
        throw new RuntimeException($label.' '.Artisan::output());
    }
    $checks++;
};
try {
    config(['database.connections.cpanel_rehearsal_server' => [...$original, 'database' => null, 'url' => null]]);
    $server = DB::connection('cpanel_rehearsal_server');
    $server->statement('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $createdDatabase = true;
    $server->unprepared("CREATE USER '".$name."'@'localhost' IDENTIFIED BY ".$server->getPdo()->quote($password));
    $createdUser = true;
    $server->unprepared("GRANT ALL PRIVILEGES ON `".$name."`.* TO '".$name."'@'localhost'");
    config(['database.connections.central' => [...$original, 'database' => $name, 'url' => null],
        'tenancy.migration_username' => $name, 'tenancy.migration_password' => $password,
        'mail.default' => 'array', 'cache.default' => 'array', 'queue.default' => 'sync']);
    DB::purge('central');
    $app->instance('env', 'production');
    putenv('CPANEL_REHEARSAL_PASSWORD=Disposable-owner-password-123');
    $options = ['--empty-database' => true, '--admin-email' => 'owner@example.test', '--admin-name' => 'Rehearsal Owner',
        '--password-env' => 'CPANEL_REHEARSAL_PASSWORD', '--no-interaction' => true];
    $check(Artisan::call('tenancy:bootstrap', $options) === 0, 'Fresh production-mode MySQL bootstrap');
    $db = DB::connection('central');
    $owner = $db->table('platform_administrators')->first();
    $check($owner && $owner->role === 'owner' && Hash::check('Disposable-owner-password-123', $owner->password), 'Owner credentials and role');
    $check($db->table('institutions')->count() === 0, 'No demo institution data');
    $check($db->table('plans')->count() === 3 && $db->table('plans')->where('signup_enabled', true)->count() === 0, 'Plans remain closed');
    $check($db->getSchemaBuilder()->hasTable('institution_portal_handoffs') && ! $db->getSchemaBuilder()->hasColumn('institution_portal_handoffs', 'tenant_id'), 'Central-only handoff table');
    $check(Artisan::call('tenancy:bootstrap', $options) !== 0 && str_contains(Artisan::output(), 'not empty'), 'Repeat bootstrap refused');
    $check($db->table('platform_administrators')->count() === 1, 'Original owner preserved');
    echo json_encode(['result' => 'success', 'checks_passed' => $checks, 'disposable_database' => $name], JSON_PRETTY_PRINT).PHP_EOL;
} finally {
    putenv('CPANEL_REHEARSAL_PASSWORD');
    DB::purge('central');
    if ($createdDatabase) {
        $server->statement('DROP DATABASE `'.$name.'`');
    }
    if ($createdUser) {
        $server->unprepared("DROP USER '".$name."'@'localhost'");
    }
    DB::purge('cpanel_rehearsal_server');
    echo 'Disposable database and migration user removed.'.PHP_EOL;
}
