<?php

use App\Models\Central\InstitutionDatabaseCopy;
use App\Services\Tenancy\InstitutionDatabaseCopies;
use App\Services\Tenancy\InstitutionProvisioner;
use App\Services\Tenancy\TenantState;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

$loader = require __DIR__.'/../../vendor/autoload.php';
$loader->setPsr4('App\\', __DIR__.'/../../app');
// Also support an isolated worktree sharing an optimized vendor directory.
$classes = [];
$appPath = realpath(__DIR__.'/../../app');
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appPath, FilesystemIterator::SKIP_DOTS)) as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $classes['App\\'.str_replace('/', '\\', substr(str_replace('\\', '/', substr($file->getPathname(), strlen($appPath) + 1)), 0, -4))] = $file->getPathname();
    }
}
$loader->addClassMap($classes);
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('local', 'testing')) {
    throw new RuntimeException('Local/test rehearsal only.');
}
$original = config('database.connections.central');
if ($original['driver'] !== 'mysql' || ! in_array($original['host'], ['localhost', '127.0.0.1'], true)) {
    throw new RuntimeException('Local MySQL required.');
}
$prefix = 'ev_copy_'.bin2hex(random_bytes(5));
$names = [$prefix.'_source', $prefix.'_a', $prefix.'_b'];
foreach ($names as $name) {
    if (! preg_match('/\Aev_copy_[a-f0-9]{10}_(source|a|b)\z/D', $name)) {
        throw new RuntimeException('Unsafe disposable name.');
    }
}
$created = $users = [];
$password = bin2hex(random_bytes(24));
$temporaryRoot = realpath(sys_get_temp_dir());
if (! $temporaryRoot) {
    throw new RuntimeException('Temporary directory unavailable.');
}
// Keep native Windows MySQL backup filenames below its path-length limit.
$root = $temporaryRoot.DIRECTORY_SEPARATOR.$prefix;
$checks = 0;
$check = static function ($condition, $label) use (&$checks) {
    if (! $condition) {
        throw new RuntimeException('Failed: '.$label);
    } $checks++;
};
try {
    config(['database.connections.copy_rehearsal_server' => [...$original, 'database' => null, 'url' => null]]);
    $server = DB::connection('copy_rehearsal_server');
    foreach ($names as $name) {
        $server->statement('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $created[] = $name;
    }
    foreach (array_slice($names, 1) as $name) {
        $server->unprepared("CREATE USER '".$name."'@'localhost' IDENTIFIED BY ".$server->getPdo()->quote($password));
        $users[] = $name;
        $server->unprepared('GRANT ALL PRIVILEGES ON `'.$name."`.* TO '".$name."'@'localhost'");
    }
    config(['database.connections.central' => [...$original, 'database' => $names[0], 'url' => null],
        'database.connections.tenant' => [...$original, 'database' => '__tenant_context_required__', 'url' => null],
        'tenancy.storage_root' => $root.'/files', 'tenancy.lock_directory' => $root.'/locks', 'tenancy.maintenance_file' => $root.'/maintenance',
        'cache.default' => 'array', 'mail.default' => 'array', 'queue.default' => 'sync']);
    DB::purge('central');
    DB::purge('tenant');
    $check(Artisan::call('tenancy:install', ['--no-interaction' => true]) === 0, 'Fresh schema rehearsal');
    $source = DB::connection('central');
    $check(! $source->getSchemaBuilder()->hasColumn('institution_database_copies', 'tenant_id'), 'Copy registry remains central');
    $schools = [];
    foreach (['mudiame', 'wellspring'] as $slug) {
        $p = app(InstitutionProvisioner::class);
        $schools[] = $p->provision($p->register(['name' => $slug, 'slug' => $slug, 'domain' => $slug.'.educore.test',
            'owner_name' => 'Owner', 'owner_email' => 'admin@example.test', 'plan_code' => 'silver', 'timezone' => 'Africa/Lagos']), 'Disposable-school-password-123');
    }
    // Recreate the previous platform schema only inside this disposable database,
    // then exercise the real backed-up upgrade rather than just a fresh install.
    $source->statement('DROP TABLE institution_database_copies');
    $source->table('migrations')->where('migration', '2026_10_20_000000_add_institution_database_copies')->delete();
    $previousStorage = $app->storagePath();
    $app->useStoragePath($root.'/runtime');
    File::ensureDirectoryExists($root.'/runtime/framework');
    if (PHP_OS_FAMILY === 'Windows' && is_file('C:/xampp/mysql/bin/mysqldump.exe')) {
        config(['backups.dump_binary' => 'C:/xampp/mysql/bin/mysqldump.exe', 'backups.mysql_binary' => 'C:/xampp/mysql/bin/mysql.exe']);
    }
    $check(Artisan::call('tenancy:upgrade', ['--writers-paused' => true]) === 0, 'Existing shared schema upgrade rehearsal');
    $check($source->getSchemaBuilder()->hasTable('institution_database_copies'), 'Upgrade creates copy configuration');
    $check($source->table('institutions')->count() === 2 && $source->table('users')->count() === 2, 'Upgrade preserves both schools and administrators');
    $app->useStoragePath($previousStorage);
    $source->statement('CREATE TABLE replica_examples (id BIGINT PRIMARY KEY, tenant_id CHAR(36) NOT NULL, payload JSON NULL, amount DECIMAL(10,2) NULL, derived INT GENERATED ALWAYS AS (id + 1) STORED, FOREIGN KEY (tenant_id) REFERENCES institutions(id)) ENGINE=InnoDB');
    $source->table('replica_examples')->insert([['id' => 1, 'tenant_id' => $schools[0]->id, 'payload' => '{"school":"Mudiame"}', 'amount' => '1.20'],
        ['id' => 2, 'tenant_id' => $schools[1]->id, 'payload' => '{"school":"Wellspring"}', 'amount' => '5.00']]);
    $copies = [];
    foreach ($schools as $index => $school) {
        $copies[] = InstitutionDatabaseCopy::create(['institution_id' => $school->id, 'database_name' => $names[$index + 1],
            'username' => $names[$index + 1], 'password' => $password, 'configuration_version' => (string) Str::uuid(), 'enabled' => true]);
    }
    $service = app(InstitutionDatabaseCopies::class);
    app(TenantState::class)->enter($schools[0]->id);
    try {
        $service->synchronize($copies[1]);
        throw new LogicException('Expected request-context refusal');
    } catch (RuntimeException) {
        $check(true, 'Institution request cannot initiate cross-school copies');
    } finally {
        app(TenantState::class)->leave();
    }
    foreach ($copies as $copy) {
        $service->synchronize($copy);
    }
    foreach (array_slice($names, 1) as $index => $name) {
        config(['database.connections.verify_copy_'.$index => [...$original, 'database' => $name, 'url' => null]]);
        $target = DB::connection('verify_copy_'.$index);
        $check($target->table('replica_examples')->count() === 1 && $target->table('replica_examples')->value('tenant_id') === $schools[$index]->id, 'Institution isolation '.$index);
        $check($target->table('users')->count() === 1 && $target->table('users')->value('tenant_id') === $schools[$index]->id, 'Staff account isolation '.$index);
        foreach (['platform_administrators', 'institutions', 'subscription_payments', 'institution_database_copies', 'jobs', 'personal_access_tokens'] as $table) {
            $check(! $target->getSchemaBuilder()->hasTable($table), 'Excluded central/security table '.$table);
        }
        $check($copies[$index]->fresh()->status === 'synced', 'Verified status '.$index);
    }
    $target = DB::connection('verify_copy_0');
    $source->table('replica_examples')->where('id', 1)->update(['payload' => '{"school":"Updated"}']);
    $service->synchronize($copies[0]->fresh());
    $check(str_contains($target->table('replica_examples')->value('payload'), 'Updated'), 'Updates replicated');
    $service->synchronize($copies[0]->fresh());
    $check($target->table('replica_examples')->count() === 1, 'Repeat synchronization idempotent');
    $target->statement('CREATE TABLE unrelated_records (id INT PRIMARY KEY) ENGINE=InnoDB');
    $target->table('unrelated_records')->insert(['id' => 9]);
    try {
        $service->synchronize($copies[0]->fresh());
        throw new LogicException('Expected unmanaged table refusal');
    } catch (RuntimeException) {
        $check($target->table('unrelated_records')->value('id') === 9, 'Unmanaged target records preserved');
    }
    $target->statement('DROP TABLE unrelated_records');
    $staging = '_ev_s_'.substr(hash('sha256', 'users'), 0, 24);
    $target->statement('CREATE TABLE `'.$staging.'` LIKE users');
    $target->table(InstitutionDatabaseCopies::STATE)->update(['pending' => json_encode([$staging])]);
    $service->synchronize($copies[0]->fresh());
    $check(! $target->getSchemaBuilder()->hasTable($staging), 'Interrupted staging recovered');
    $bad = clone $copies[0];
    $bad->password = 'Wrong-disposable-password';
    try {
        $service->synchronize($bad);
        throw new LogicException('Expected invalid credentials refusal');
    } catch (RuntimeException $exception) {
        $check(! str_contains($exception->getMessage(), 'Wrong-disposable-password'), 'Connection failures redacted');
    }
    $check($target->table('replica_examples')->count() === 1, 'Connection failure preserves prior snapshot');
    $source->table('institution_database_copies')->where('institution_id', $copies[0]->institution_id)->update(['password' => 'invalid-ciphertext']);
    try {
        $service->synchronize($copies[0]->fresh());
        throw new LogicException('Expected encrypted credential refusal');
    } catch (RuntimeException $exception) {
        $check($copies[0]->fresh()->failure_code === 'connection', 'Encrypted credential failures monitored safely');
    }
    $source->table('institution_database_copies')->where('institution_id', $copies[0]->institution_id)
        ->update(['password' => $copies[0]->getRawOriginal('password')]);
    config(['tenancy.database_copies.max_rows' => 0]);
    $source->table('replica_examples')->where('id', 1)->delete();
    try {
        $service->synchronize($copies[0]->fresh());
        throw new LogicException('Expected limit refusal');
    } catch (RuntimeException $exception) {
        $check(! str_contains($exception->getMessage(), $password), 'Failure output redacts credentials');
    }
    $check($target->table('replica_examples')->count() === 1, 'Failed snapshot preserves last activated copy');
    $check($source->table('replica_examples')->where('tenant_id', $schools[0]->id)->count() === 0, 'Primary writes preserved during failure');
    config(['tenancy.database_copies.max_rows' => 100000]);
    $source->statement('ALTER TABLE replica_examples ADD COLUMN new_field VARCHAR(64) NULL');
    $service->synchronize($copies[0]->fresh());
    $check($target->table('replica_examples')->count() === 0, 'Deletion replicated after retry');
    $check($target->getSchemaBuilder()->hasColumn('replica_examples', 'new_field'), 'Schema evolution replicated');
    $check(count($target->getSchemaBuilder()->getTableListing($names[1], schemaQualified: false)) > 0, 'Snapshot tables retained');
    $foreign = clone $copies[1];
    $foreign->database_name = $copies[0]->database_name;
    $foreign->username = $copies[0]->username;
    try {
        $service->synchronize($foreign);
        throw new LogicException('Expected ownership refusal');
    } catch (RuntimeException) {
        $check(true, 'Cross-school target refused');
    }
    $check($target->table(InstitutionDatabaseCopies::STATE)->value('institution_id') === $schools[0]->id, 'Ownership preserved');
    $check($source->table('replica_examples')->where('tenant_id', $schools[1]->id)->count() === 1, 'Other source school unchanged');
    config(['database.connections.limited_copy_user' => [...$original, 'database' => $names[1], 'username' => $names[1], 'password' => $password, 'url' => null]]);
    try {
        DB::connection('limited_copy_user')->select('SELECT * FROM `'.$names[0].'`.users');
        throw new LogicException('Expected permission refusal');
    } catch (QueryException) {
        $check(true, 'Dedicated user cannot read shared source');
    }
    config(['queue.connections.database.connection' => 'central', 'queue.failed.database' => 'central', 'queue.batching.database' => 'central']);
    $check(Artisan::call('tenancy:copies-dispatch') === 0, 'Real queue dispatch');
    $queued = $source->table('jobs')->where('queue', 'default')->get();
    $check($queued->count() === 2 && ! str_contains($queued->pluck('payload')->implode(''), $password), 'Queue payloads contain no database passwords');
    Artisan::call('tenancy:copies-dispatch');
    $check($source->table('jobs')->where('queue', 'default')->count() === 2, 'Repeated dispatch creates no duplicate jobs');
    for ($run = 0; $run < 2; $run++) {
        $check(Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'default', '--once' => true, '--tries' => 1]) === 0, 'Actual central worker completes copy job');
    }
    $check($copies[0]->fresh()->status === 'synced' && $copies[1]->fresh()->status === 'synced', 'Both queued institution copies verified');
    echo json_encode(['result' => 'success', 'checks_passed' => $checks, 'mode' => 'disposable MySQL snapshot rehearsal'], JSON_PRETTY_PRINT).PHP_EOL;
} finally {
    if (isset($previousStorage)) {
        $app->useStoragePath($previousStorage);
    }
    foreach (['central', 'tenant', 'institution_copy', 'verify_copy_0', 'verify_copy_1', 'limited_copy_user'] as $connection) {
        DB::purge($connection);
    }
    foreach (array_reverse($created) as $name) {
        $server->statement('DROP DATABASE `'.$name.'`');
    }
    foreach ($users as $name) {
        $server->unprepared("DROP USER '".$name."'@'localhost'");
    }
    if (is_dir($root)) {
        $resolvedRoot = realpath($root);
        if (! $resolvedRoot || dirname($resolvedRoot) !== $temporaryRoot || basename($resolvedRoot) !== $prefix) {
            throw new RuntimeException('Refusing cleanup outside the disposable temporary directory.');
        }
        File::deleteDirectory($resolvedRoot);
    }
    echo 'Disposable databases, users and files removed.'.PHP_EOL;
}
