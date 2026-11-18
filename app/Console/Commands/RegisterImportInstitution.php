<?php

namespace App\Console\Commands;

use App\Models\Central\Institution;
use App\Services\Tenancy\InstitutionAudit;
use App\Services\Tenancy\InstitutionProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Throwable;

class RegisterImportInstitution extends Command
{
    protected $signature = 'tenancy:register-import {slug} {--name=} {--domain=} {--owner-name=} {--owner-email=} {--plan=silver} {--timezone=Africa/Lagos} {--reason=}';

    protected $description = 'Create an empty pending institution record for a reviewed legacy-data import';

    public function handle(InstitutionProvisioner $provisioner): int
    {
        $data = [
            'slug' => $this->argument('slug'),
            'name' => $this->option('name'),
            'domain' => $this->option('domain'),
            'owner_name' => $this->option('owner-name'),
            'owner_email' => $this->option('owner-email'),
            'plan' => $this->option('plan'),
            'timezone' => $this->option('timezone'),
            'reason' => $this->option('reason'),
        ];
        $validator = Validator::make($data, [
            'slug' => 'required|string|max:63|regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D',
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:253',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|max:255',
            'plan' => 'required|string|max:32',
            'timezone' => 'required|timezone',
            'reason' => 'required|string|min:20|max:2000',
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        try {
            if (Institution::where('slug', $data['slug'])->exists()) {
                throw new \RuntimeException('That institution slug already exists. Do not reuse a provisioned or populated institution for an import.');
            }
            $institution = $provisioner->register([
                'slug' => $data['slug'],
                'name' => $data['name'],
                'domain' => $data['domain'],
                'owner_name' => $data['owner_name'],
                'owner_email' => $data['owner_email'],
                'plan_code' => $data['plan'],
                'timezone' => $data['timezone'],
            ]);
            $institution->forceFill(['settings' => ['domain_pending_verification' => true]])->save();
            InstitutionAudit::record($institution->id, 'legacy_import_target_registered', 'operator', [
                'domain' => $data['domain'],
                'reason' => $data['reason'],
            ]);

            $this->info('Empty pending import target created: '.$institution->slug.' ('.$institution->id.').');
            $this->line('Do not provision it. Review its domain, then run tenancy:import-legacy in review-only mode first.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Import target registration failed ('.$exception::class.').');
            if ($exception instanceof \RuntimeException) {
                $this->error($exception->getMessage());
            }

            return self::FAILURE;
        }
    }
}
