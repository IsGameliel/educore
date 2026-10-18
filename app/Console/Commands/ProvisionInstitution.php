<?php

namespace App\Console\Commands;

use App\Services\Tenancy\InstitutionProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ProvisionInstitution extends Command
{
    protected $signature = 'tenancy:provision {slug} {--name=} {--domain=} {--owner-name=} {--owner-email=}
        {--plan=silver} {--timezone=Africa/Lagos} {--password-env=TENANT_ADMIN_PASSWORD}';

    protected $description = 'Provision institution-owned records and first administrator in the shared database';

    public function handle(InstitutionProvisioner $provisioner): int
    {
        try {
            $password = getenv($this->option('password-env')) ?: ($this->input->isInteractive() ? $this->secret('Initial school administrator password (12+ characters)') : null);
            // Validate before reserving institutional records.
            Validator::make(['password' => $password], ['password' => 'required|string|min:12|max:255'])->validate();
            $institution = $provisioner->register([
                'slug' => $this->argument('slug'), 'name' => $this->option('name'), 'domain' => $this->option('domain'),
                'owner_name' => $this->option('owner-name'), 'owner_email' => $this->option('owner-email'),
                'plan_code' => $this->option('plan'), 'timezone' => $this->option('timezone'),
            ]);
            $institution = $provisioner->provision($institution, $password);
            $this->info('Institution provisioned: '.$institution->slug.' ('.$institution->id.')');
            $this->line('Shared database: '.config('database.connections.'.config('tenancy.central_connection').'.database').'; status: '.$institution->status);
            $this->warn('Provisioning does not activate paid access or enable tenant login routes.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            // Do not expose SQL, passwords, or configuration in console output.
            $this->error('Provisioning failed ('.$exception::class.'). Review the central provisioning status and configured permissions; rerun with the same details.');
            if ($exception::class === \RuntimeException::class) {
                $this->error($exception->getMessage());
            }

            return self::FAILURE;
        }
    }
}
