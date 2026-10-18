<?php

namespace App\Services\Tenancy;

use App\Models\Central\Institution;
use App\Models\Central\Plan;
use App\Models\Central\SubscriptionInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportedInstitutionActivation
{
    public function activate(Institution $school, array $terms): SubscriptionInvoice
    {
        Validator::make($terms, ['plan_code' => 'required|string', 'semester_name' => 'required|string|max:255', 'semester_start' => 'required|date_format:Y-m-d', 'semester_end' => 'required|date_format:Y-m-d|after:semester_start', 'student_count' => 'required|integer|min:1|max:100000', 'reason' => 'required|string|min:20|max:2000', 'agreed_no_charge' => 'accepted'])->validate();
        if (Carbon::parse($terms['semester_start'])->diffInDays(Carbon::parse($terms['semester_end'])) > 366) {
            throw ValidationException::withMessages(['semester_end' => 'Maximum 366 days.']);
        }

        return app(InstitutionMutex::class)->run($school->id, fn () => DB::connection(config('tenancy.central_connection'))->transaction(function () use ($school, $terms) {
            $school = $school->fresh();
            if ($school->status !== 'imported' || SubscriptionInvoice::where('institution_id', $school->id)->exists()) {
                throw new \RuntimeException('Only a reviewed, newly imported institution without subscription invoices can activate.');
            }
            $plan = Plan::with('features')->findOrFail($terms['plan_code']);
            $features = $plan->features->where('implementation_status', '!=', 'not_found')->sortBy('code')->map->only(['code', 'name', 'implementation_status'])->values()->all();
            $invoice = SubscriptionInvoice::create(['institution_id' => $school->id, 'plan_code' => $plan->code, 'plan_name' => $plan->name, 'feature_snapshot' => $features,
                'semester_name' => $terms['semester_name'], 'semester_start' => $terms['semester_start'], 'semester_end' => $terms['semester_end'], 'student_count' => (int) $terms['student_count'],
                'rate_minor' => 0, 'standard_rate_minor' => $plan->standard_rate_minor, 'amount_minor' => 0, 'currency' => 'NGN', 'promotional' => false, 'price_version' => hash('sha256', json_encode($terms)),
                'billing_policy' => 'agreed-migration-no-charge-v1', 'status' => 'paid', 'paid_at' => now()]);
            $school->forceFill(['status' => 'ready', 'plan_code' => $plan->code, 'subscription_status' => app(SemesterBilling::class)->status($school)])->save();
            InstitutionAudit::record($school->id, 'import_activated', 'operator', ['invoice_id' => $invoice->id, 'reason' => $terms['reason'], 'policy' => 'agreed-migration-no-charge-v1']);

            return $invoice;
        }));
    }
}
