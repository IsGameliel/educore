<?php

namespace App\Console\Commands;

use App\Models\Central\Institution;
use App\Services\Tenancy\ImportedInstitutionActivation;
use Illuminate\Console\Command;

class ActivateImportedInstitution extends Command
{
    protected $signature = 'tenancy:activate-import {institution} {--plan=} {--semester=} {--from=} {--until=} {--students=} {--reason=} {--agreed-no-charge : Confirm owner-approved complimentary migration period}';

    protected $description = 'Apply explicitly agreed no-charge semester terms to a reviewed imported school';

    public function handle(ImportedInstitutionActivation $activation): int
    {
        $school = Institution::where('slug', $this->argument('institution'))->firstOrFail();
        $activation->activate($school, ['plan_code' => $this->option('plan'), 'semester_name' => $this->option('semester'), 'semester_start' => $this->option('from'), 'semester_end' => $this->option('until'), 'student_count' => $this->option('students'), 'reason' => $this->option('reason'), 'agreed_no_charge' => $this->option('agreed-no-charge')]);
        $this->info('Imported school activated for the explicitly agreed period; no payment was requested or fabricated.');

        return self::SUCCESS;
    }
}
