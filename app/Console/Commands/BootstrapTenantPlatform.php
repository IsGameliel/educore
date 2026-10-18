<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BootstrapTenantPlatform extends Command
{
    protected $signature = 'tenancy:bootstrap {--empty-database : Confirm this is a new, empty shared database}
        {--admin-email= : First platform owner email} {--admin-name= : First platform owner name}
        {--password-env=PLATFORM_ADMIN_PASSWORD : Environment variable containing the initial password}';

    protected $description = 'Install a fresh shared platform and owner; refuse existing databases';

    public function handle(): int
    {
        if (! $this->option('empty-database')) {
            $this->error('Create a new empty database, then supply --empty-database. Existing platforms use tenancy:upgrade.');
            return self::FAILURE;
        }
        $name = config('tenancy.central_connection');
        $original = config('database.connections.'.$name);
        $previousUpgrading = config('tenancy.upgrading');
        $passwordVariable = $this->option('password-env');
        if (! preg_match('/\A[A-Z_][A-Z0-9_]*\z/D', $passwordVariable)) {
            $this->error('Choose a valid password environment variable name.');
            return self::FAILURE;
        }
        $previousPassword = getenv($passwordVariable);
        try {
            $email = strtolower(trim($this->option('admin-email') ?? ''));
            $adminName = trim($this->option('admin-name') ?: 'Platform Owner');
            // Validate credentials before any schema changes; never print the password.
            $password = $previousPassword ?: ($this->input->isInteractive() ? $this->secret('Initial platform owner password (12+ characters)') : null);
            Validator::make(['email' => $email, 'name' => $adminName, 'password' => $password], [
                'email' => 'required|email|max:255', 'name' => 'required|string|max:255', 'password' => 'required|string|min:12|max:255',
            ])->validate();
            if (app()->environment('production')) {
                $migrationUsername = getenv('TENANCY_MIGRATION_USERNAME') ?: config('tenancy.migration_username');
                $migrationPassword = getenv('TENANCY_MIGRATION_PASSWORD') ?: config('tenancy.migration_password');
                if (! $migrationUsername || ! $migrationPassword) {
                    $this->error('Supply dedicated TENANCY_MIGRATION_USERNAME and TENANCY_MIGRATION_PASSWORD to this terminal process.');
                    return self::FAILURE;
                }
                config(['database.connections.'.$name => [...$original,
                    'username' => $migrationUsername, 'password' => $migrationPassword, 'url' => null]]);
                DB::purge($name);
            }
            $db = DB::connection($name);
            if ($name === DB::getDefaultConnection() || $db->getDatabaseName() === DB::connection()->getDatabaseName()) {
                $this->error('The shared database must be separate from the default/support database.');
                return self::FAILURE;
            }
            $schema = $db->getDriverName() === 'sqlite' ? 'main' : $db->getDatabaseName();
            if ($db->getSchemaBuilder()->getTableListing($schema, schemaQualified: false)) {
                $this->error('Target database is not empty. Nothing changed. Use tenancy:upgrade for an existing platform.');
                return self::FAILURE;
            }
            config(['tenancy.upgrading' => true]);
            putenv($passwordVariable.'='.$password);
            $result = $this->call('tenancy:install', ['--admin-email' => $email, '--admin-name' => $adminName,
                '--password-env' => $passwordVariable, '--no-interaction' => true]);
            if ($result === self::SUCCESS) {
                $this->info('Fresh platform installed. Sign in at '.rtrim(config('tenancy.platform_url'), '/').'/dashboard/login.');
                $this->warn('Signup plans start disabled. Configure test payments, domains and mail before enabling test signup.');
            }
            return $result;
        } catch (\Illuminate\Validation\ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }
            return self::FAILURE;
        } catch (\Throwable $exception) {
            $this->error('First installation failed ('.$exception::class.'). Inspect private server logs and database state before retrying.');
            return self::FAILURE;
        } finally {
            putenv($previousPassword === false ? $passwordVariable : $passwordVariable.'='.$previousPassword);
            config(['tenancy.upgrading' => $previousUpgrading, 'database.connections.'.$name => $original]);
            DB::purge($name);
        }
    }
}
