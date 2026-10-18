<?php

namespace App\Services\Tenancy;

use App\Models\Central\Institution;
use App\Models\Central\Plan;
use App\Models\Central\SubscriptionInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SemesterBilling
{
    public function issue(Institution $institution, Plan $plan, array $data): SubscriptionInvoice
    {
        \Illuminate\Support\Facades\Validator::make($data, [
            'semester_name' => 'required|string|max:255', 'semester_start' => 'required|date_format:Y-m-d',
            'semester_end' => 'required|date_format:Y-m-d|after:semester_start',
            'student_count' => 'required|integer|min:1|max:100000',
        ])->validate();
        if (\Illuminate\Support\Carbon::parse($data['semester_start'])->diffInDays(\Illuminate\Support\Carbon::parse($data['semester_end'])) > 366) {
            throw ValidationException::withMessages(['semester_end' => 'A semester cannot exceed 366 days.']);
        }

        return DB::connection(config('tenancy.central_connection'))->transaction(function () use ($institution, $plan, $data) {
            $institution = Institution::lockForUpdate()->findOrFail($institution->id);
            $plan = Plan::with('features')->lockForUpdate()->findOrFail($plan->code);
            $invoices = SubscriptionInvoice::where('institution_id', $institution->id)->orderBy('semester_start')->get();
            $existing = $invoices->first(fn ($invoice) => $invoice->semester_start->format('Y-m-d') === $data['semester_start']);
            if ($existing) {
                if ($existing->plan_code !== $plan->code || $existing->student_count !== (int) $data['student_count']
                    || $existing->semester_end?->format('Y-m-d') !== ($data['semester_end'] ?? null)
                    || $existing->semester_name !== $data['semester_name']) {
                    throw ValidationException::withMessages(['semester_start' => 'An immutable invoice already exists for this semester. Open it to continue payment.']);
                }

                return $existing;
            }
            if ($invoices->contains(fn ($invoice) => $invoice->status !== 'paid')) {
                throw ValidationException::withMessages(['semester_start' => 'Complete the outstanding semester invoice before creating another.']);
            }
            $previous = $invoices->last();
            if ($previous && $data['semester_start'] <= ($previous->semester_end ?? $previous->semester_start)->format('Y-m-d')) {
                throw ValidationException::withMessages(['semester_start' => 'The next semester must begin after the previous semester ends.']);
            }
            if (! $plan->signup_enabled) {
                throw ValidationException::withMessages(['plan_code' => 'This plan is currently unavailable.']);
            }
            $features = $plan->features->where('implementation_status','!=','not_found')->sortBy('code')->map->only(['code', 'name', 'implementation_status'])->values()->all();
            $promotion = $invoices->isEmpty();
            $rate = $promotion ? $plan->promotional_rate_minor : $plan->standard_rate_minor;
            $invoice = SubscriptionInvoice::create([
                'institution_id' => $institution->id, 'plan_code' => $plan->code, 'plan_name' => $plan->name,
                'feature_snapshot' => $features, 'semester_name' => $data['semester_name'],
                'semester_start' => $data['semester_start'], 'semester_end' => $data['semester_end'] ?? null,
                'student_count' => (int) $data['student_count'], 'rate_minor' => $rate,
                'standard_rate_minor' => $plan->standard_rate_minor, 'amount_minor' => (int) $data['student_count'] * $rate,
                'currency' => 'NGN', 'promotional' => $promotion,
                'price_version' => hash('sha256', json_encode([$plan->code, $plan->standard_rate_minor, $plan->promotional_rate_minor, $features])),
                'billing_policy' => 'semester-fixed-v1',
            ]);
            $this->record($institution->id, 'invoice_issued', ['invoice_id' => $invoice->id, 'price_version' => $invoice->price_version,
                'count_basis' => 'owner_declared_semester_start', 'amount_minor' => $invoice->amount_minor]);

            return $invoice;
        });
    }

    public function current(Institution $institution): ?SubscriptionInvoice
    {
        $today = now($institution->timezone)->toDateString();

        $invoice = SubscriptionInvoice::where('institution_id', $institution->id)
            ->whereDate('semester_start', '<=', $today)
            ->orderByDesc('semester_start')->first();

        // A later semester supersedes an old open-ended purchase, even if unpaid.
        // Never fall back to an earlier package after a renewal expires.
        if ($invoice && $invoice->status === 'paid'
            && (! $invoice->semester_end || $invoice->semester_end->format('Y-m-d') >= $today)) {
            return $invoice;
        }

        // Verified payment opens a new school's portal immediately. Keep an existing
        // current package above until it ends; otherwise use the next paid package.
        // Invoice dates, prices and enrollment remain unchanged.
        return SubscriptionInvoice::where('institution_id', $institution->id)->where('status', 'paid')
            ->whereDate('semester_start', '>', $today)->orderBy('semester_start')->first();
    }

    public function status(Institution $institution): string
    {
        if ($institution->subscription_status === 'not_required') {
            return 'not_required';
        }
        if ($this->current($institution)) {
            return 'active';
        }
        $paid = SubscriptionInvoice::where('institution_id', $institution->id)->where('status', 'paid');
        return $paid->exists() ? 'expired' : 'awaiting_payment';
    }

    public function accessible(Institution $institution): bool
    {
        return $institution->subscription_status === 'not_required' || $this->current($institution) !== null;
    }

    public function record(string $institutionId, string $event, array $details): void
    {
        DB::connection(config('tenancy.central_connection'))->table('subscription_events')->insert([
            'institution_id' => $institutionId, 'event' => $event, 'details' => json_encode($details), 'created_at' => now(),
        ]);
    }
}
