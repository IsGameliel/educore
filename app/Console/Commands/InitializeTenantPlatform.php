<?php

namespace App\Console\Commands;

use App\Models\Central\PlatformAdministrator;
use App\Services\Tenancy\PlanCatalogue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class InitializeTenantPlatform extends Command
{
    protected $signature = 'tenancy:install {--create-database : Create the shared development database if absent}
        {--admin-email= : Optional first platform administrator email}
        {--admin-name= : Optional first platform administrator name}
        {--password-env=PLATFORM_ADMIN_PASSWORD : Environment variable containing an initial password}';

    protected $description = 'Initialize central records and shared institution tables without migrating the live school';

    public function handle(PlanCatalogue $catalogue): int
    {
        if (app()->environment('production') && ! config('tenancy.upgrading')) { $this->error('Use tenancy:upgrade with dedicated migration credentials in production.'); return self::FAILURE; }
        $name = config('tenancy.central_connection');
        $previousDefault = DB::getDefaultConnection();
        try {
            $central = config('database.connections.'.$name);
            if ($name === $previousDefault || ($central['database'] ?? null) === DB::connection()->getDatabaseName()) {
                throw new RuntimeException('Development platform must be separate from the existing live institution until cutover.');
            }
            if ($this->option('create-database')) {
                if (($central['driver'] ?? '') !== 'mysql' || ! preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $central['database'])) {
                    throw new RuntimeException('Automatic database creation requires MySQL and a safe identifier.');
                }
                $serverName = 'central_install_server';
                config(['database.connections.'.$serverName => [...$central, 'database' => null, 'url' => null]]);
                try {
                    $server = DB::connection($serverName);
                    if (! $server->table('information_schema.SCHEMATA')->where('SCHEMA_NAME', $central['database'])->exists()) {
                        $server->statement('CREATE DATABASE `'.$central['database'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                    }
                } finally {
                    DB::purge($serverName);
                    config(['database.connections.'.$serverName => null]);
                }
            }
            $connection = DB::connection($name);
            $schema = $connection->getDriverName() === 'sqlite' ? 'main' : $connection->getDatabaseName();
            $tables = $connection->getSchemaBuilder()->getTableListing($schema, schemaQualified: false);
            if ($tables && (! $connection->getSchemaBuilder()->hasTable('tenancy_schema')
                || $connection->table('tenancy_schema')->value('architecture') !== 'shared_database')) {
                throw new RuntimeException('Target is not an initialized shared platform; refusing unrelated data.');
            }
            DB::setDefaultConnection($name);
            foreach ([config('tenancy.central_migrations'), ...config('tenancy.tenant_migrations')] as $path) {
                if (Artisan::call('migrate', ['--database' => $name, '--path' => [$path], '--realpath' => true, '--force' => true]) !== 0) {
                    throw new RuntimeException('Shared platform migrations failed.');
                }
            }
            $catalogue->seed();
            if ($email = $this->option('admin-email')) {
                $email = strtolower(trim($email));
                $adminName = $this->option('admin-name') ?: 'Platform Administrator';
                Validator::make(['email' => $email, 'name' => $adminName], ['email' => 'required|email|max:255', 'name' => 'required|string|max:255'])->validate();
                if (! PlatformAdministrator::where('email', $email)->exists()) {
                    $password = getenv($this->option('password-env')) ?: ($this->input->isInteractive() ? $this->secret('Initial platform administrator password (12+ characters)') : null);
                    Validator::make(['password' => $password], ['password' => 'required|string|min:12|max:255'])->validate();
                    $administrator = new PlatformAdministrator(['name' => $adminName, 'email' => $email, 'password' => $password]);
                    $administrator->role = 'owner';
                    $administrator->save();
                }
            }
            $this->info('Shared platform schema and three-tier catalogue initialized. Existing institution unchanged.');
            $this->info('Manage plan availability in the platform dashboard. Configure PLATFORM_PAYSTACK_SECRET_KEY before subscription checkout.');

            return self::SUCCESS;
        } catch (ValidationException $exception) {
            $this->error('Platform administrator details are invalid:');
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error('Platform initialization failed ('.$exception::class.'). Check configuration and database permissions.');
            if ($exception::class === RuntimeException::class) {
                $this->error($exception->getMessage());
            }

            return self::FAILURE;
        } finally {
            DB::setDefaultConnection($previousDefault);
        }
    }
}
