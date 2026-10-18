<?php

namespace App\Services\Tenancy;

use App\Models\Central\Feature;

class LaunchCatalogue
{
    public const REVIEW = [
        'classroom_management' => ['Class schedules and conflict checks', 'implemented', 'Manual schedules, room/lecturer/cohort conflict checks and student timetable views. No automatic timetable generation.'],
        'school_information' => ['School information and branding', 'implemented', 'Faculties, departments and institution branding/contact settings.'],
        'application_configuration' => ['Academic and institution settings', 'implemented', 'Registration policies, academic sessions, promotion policies, branding and timezone.'],
        'staff_management' => ['Staff HR management', 'not_found', 'Deferred: employment records, leave, HR permissions and payroll integration. Basic staff accounts remain included under user management.'],
        'mobile_application' => ['Dedicated mobile application', 'not_found', 'Deferred: no packaged mobile client. Responsive browser access is available; the existing API is not a complete mobile product.'],
        'assignment_management' => ['Assignments and submissions', 'not_found', 'Deferred: assignments, student submissions, marking and feedback.'],
        'wallet' => ['Stored-value wallet', 'not_found', 'Deferred: no wallet balances, ledger, funding, transfers or withdrawals. Tuition payments are not wallet balances.'],
        'e_learning' => ['Course materials library', 'partial', 'Included scope: authorized upload, filtering and download of course materials. Lessons, progress tracking, live teaching and assignments are excluded.'],
        'mailing' => ['Bulk mailing campaigns', 'not_found', 'Deferred: recipient targeting, consent, unsubscribe, quotas and delivery tracking. Essential account/payment notifications remain available.'],
        'dynamic_timetable' => ['Automatic timetable generation', 'not_found', 'Deferred: scheduling solver, constraints, reviewed generation and publication. Manual schedules and conflict checks remain in Silver.'],
    ];

    public function apply(): void
    {
        foreach (self::REVIEW as $code => [$name,$status,$notes]) {
            Feature::whereKey($code)->update(['name' => $name, 'implementation_status' => $status, 'notes' => $notes]);
        }
    }
}
