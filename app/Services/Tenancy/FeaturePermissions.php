<?php

namespace App\Services\Tenancy;

use App\Models\Central\Institution;
use App\Models\Central\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeaturePermissions
{
    public function allows(string $feature): bool
    {
        $id = app(TenantState::class)->id();
        if (! $id) {
            return true; // Preserve the existing single-school application.
        }
        $institution = Institution::findOrFail($id);
        if ($institution->status !== 'ready' || ! app(SemesterBilling::class)->accessible($institution)) {
            return false;
        }
        if (\App\Models\Central\Feature::find($feature)?->implementation_status === 'not_found') {
            return false;
        }
        $override = DB::connection(config('tenancy.central_connection'))->table('institution_feature_overrides')
            ->where('institution_id', $id)->where('feature_code', $feature)->where('expires_at', '>', now())->orderByDesc('id')->first();
        if ($override) {
            return (bool) $override->allowed;
        }
        $invoice = app(SemesterBilling::class)->current($institution);
        $features = $invoice ? collect($invoice->feature_snapshot)
            : (Plan::with('features')->find($institution->plan_code)?->features ?? collect());

        return $features->contains(fn ($item) => data_get($item, 'code') === $feature && data_get($item, 'implementation_status') !== 'not_found');
    }

    public function authorize(string $feature): void
    {
        abort_unless($this->allows($feature), 403, 'Your institution subscription does not include this feature. Contact your school administrator to review the plan for the next semester.');
    }

    public function forTable(string $table): ?string
    {
        return match ($table) {
            'results', 'result_appeals', 'result_corrections', 'result_revisions', 'transcript_documents', 'transcript_requests' => 'result_management',
            'tests', 'questions', 'responses', 'test_attempts' => 'cbt',
            'attendance_records', 'attendance_sessions' => 'attendance',
            'course_materials' => 'e_learning',
            default => null,
        };
    }

    public function forRoute(?string $name, ?string $controller = null, ?string $method = null): ?string
    {
        $name ??= '';
        if (! $controller && $name && ($route = app('router')->getRoutes()->getByName($name))) {
            [$controller, $method] = array_pad(explode('@', $route->getActionName()), 2, null);
        }
        // Basic staff account administration and fixed class schedules remain Silver.
        $groups = [
            'cbt' => ['*.tests.*'],
            'attendance' => ['*.attendance.*', 'attendance.*', 'academic.assistance'],
            'e_learning' => ['*.course-materials*'],
            'result_management' => ['student.services', 'academic.*', '*.results.*', 'results.*', 'documents.transcripts.*', 'academic.verify'],
        ];
        foreach ($groups as $feature => $patterns) {
            if (Str::is($patterns, $name)) {
                return $feature;
            }
        }
        return match (class_basename($controller ?? '')) {
            'TestController' => 'cbt',
            'AttendanceController' => 'attendance',
            'CourseMaterialController' => 'e_learning',
            'ResultController', 'AcademicPortalController' => 'result_management',
            'StudentController' => $method === 'courseMaterial' ? 'e_learning' : null,
            'HomeController' => 'portal_control',
            'FacultyController', 'DepartmentController' => 'school_information',
            'CourseController', 'CourseRegistrationController', 'AdminCourseRegistrationController', 'RegistrationEditController' => 'course_management',
            'ClassScheduleController', 'StudentScheduleController' => 'classroom_management',
            'StudentManagementController', 'AdmittedStudentController', 'AdmissionController', 'StudentIdCardController', 'StudentAccountMergeController' => 'student_records',
            'StaffManagementController' => 'user_management',
            'PaymentController', 'TuitionController', 'TuitionTemplateController', 'FinancialControlController' => 'payment_management',
            'InstitutionSettingsController' => in_array($method, ['edit', 'update'], true) ? 'application_configuration' : null,
            'RegistrationSettingsController', 'PromotionPolicyController', 'AcademicSessionController' => 'application_configuration',
            default => null,
        };
    }
}
