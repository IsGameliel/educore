<?php

namespace App\Services\Tenancy;

use App\Models\Central\Feature;
use App\Models\Central\Plan;
use Illuminate\Support\Facades\DB;

class PlanCatalogue
{
    public function seed(): void
    {
        DB::connection(config('tenancy.central_connection'))->transaction(function () {
            $tiers = [
                'silver' => [200000, 100000, [
                    ['portal_control', 'Portal Control System', 'implemented', 'Dashboard and role-based portal access.'],
                    ['classroom_management', 'Classroom Management', 'partial', 'Courses and class schedules exist; define the boundary with dynamic timetable.'],
                    ['student_records', 'Student Record Management System', 'implemented', 'Students, admissions, academic history and account maintenance.'],
                    ['school_information', 'School Information Management System', 'partial', 'Faculties and departments exist; tenant branding/settings are planned.'],
                    ['course_management', 'Course Management System', 'implemented', 'Courses, prerequisites and registrations.'],
                    ['payment_management', 'Payment Management System', 'implemented', 'Paystack payments and tuition finance workflows.'],
                    ['user_management', 'User Management and Control', 'implemented', 'Users, roles, invitations and account controls.'],
                    ['application_configuration', 'Application Management and Configuration', 'partial', 'Academic settings exist; tenant-level configuration is planned.'],
                    ['mobile_application', 'Mobile Application', 'not_found', 'No dedicated mobile application found in this repository.'],
                ]],
                'gold' => [450000, 225000, [
                    ['staff_management', 'Staff Management System', 'partial', 'Staff CRUD and dashboards exist; review the intended full module.'],
                    ['assignment_management', 'Assignment Management System', 'not_found', 'No dedicated assignment/submission/grading module found.'],
                    ['wallet', 'Wallet System', 'not_found', 'No wallet ledger or wallet module found.'],
                    ['result_management', 'Result Management System', 'implemented', 'Results, grading, appeals, corrections and transcripts.'],
                ]],
                'platinum' => [650000, 325000, [
                    ['e_learning', 'E-Learning', 'partial', 'Course materials exist; a complete learning platform is not established.'],
                    ['mailing', 'Mailing System', 'partial', 'Transactional emails exist; bulk mailing/campaign module not found.'],
                    ['cbt', 'Computer-Based Testing', 'implemented', 'Tests, questions, attempts and responses.'],
                    ['dynamic_timetable', 'Dynamic Timetable System', 'partial', 'Class scheduling and conflict services exist; review dynamic requirements.'],
                    ['attendance', 'Attendance Management System', 'implemented', 'Attendance sessions, records and scan flows.'],
                ]],
            ];
            $inherited = [];
            foreach ($tiers as $code => [$standard, $promotional, $features]) {
                // Never reset edited prices or availability on repeated initialization.
                $plan = Plan::firstOrCreate(['code' => $code], [
                    'name' => ucfirst($code), 'currency' => 'NGN', 'billing_unit' => 'student_per_semester',
                    'standard_rate_minor' => $standard, 'promotional_rate_minor' => $promotional,
                    'promotion_semesters' => 1,
                    'signup_enabled' => false,
                ]);
                foreach ($features as [$featureCode, $name, $status, $notes]) {
                    Feature::firstOrCreate(['code' => $featureCode], ['name' => $name, 'implementation_status' => $status, 'notes' => $notes]);
                    $inherited[] = $featureCode;
                }
                if ($plan->wasRecentlyCreated) {
                    $plan->features()->sync($inherited);
                }
            }
            app(LaunchCatalogue::class)->apply();
        });
    }
}
