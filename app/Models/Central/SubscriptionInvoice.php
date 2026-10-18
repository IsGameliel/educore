<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SubscriptionInvoice extends CentralModel
{
    use HasUuids;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(function (self $invoice) {
            $fixed = ['institution_id', 'plan_code', 'plan_name', 'feature_snapshot', 'semester_name', 'semester_start', 'semester_end',
                'student_count', 'rate_minor', 'standard_rate_minor', 'amount_minor', 'currency', 'promotional', 'price_version', 'billing_policy'];
            if ($invoice->isDirty($fixed)) {
                throw new \RuntimeException('Issued semester invoice details are immutable.');
            }
        });
    }

    protected function casts(): array
    {
        return ['feature_snapshot' => 'array', 'semester_start' => 'date', 'semester_end' => 'date', 'promotional' => 'boolean',
            'student_count' => 'integer', 'rate_minor' => 'integer', 'amount_minor' => 'integer', 'paid_at' => 'datetime'];
    }
}
