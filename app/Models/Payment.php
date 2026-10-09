<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const FEES = ['application' => 1000000, 'transcript' => 3000000, 'appeal' => 1500000, 'late_registration' => 500000];
    public const LABELS = ['application' => 'Application fee', 'transcript' => 'Transcript request', 'appeal' => 'Result appeal', 'tuition' => 'Tuition fee', 'late_registration' => 'Late course registration'];

    protected $guarded = ['id'];

    protected $casts = ['amount' => 'integer', 'paid_at' => 'datetime', 'verified_at' => 'datetime',
        'gateway_deduction' => 'integer', 'gateway_reversed' => 'boolean', 'dispute_open' => 'boolean',
        'financial_checked_at' => 'datetime', 'financial_attempted_at' => 'datetime', 'reconciliation_attempted_at' => 'datetime', 'initialization_attempted_at'=>'datetime'];

    public function gatewayChanges() { return $this->hasMany(PaymentGatewayChange::class); }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function payable()
    {
        return $this->morphTo();
    }

    public function tuitionInvoice()
    {
        return $this->belongsTo(TuitionInvoice::class);
    }

    public function receiptNumber(): string
    {
        return 'EDU-RCT-'.str_pad((string) $this->id, 8, '0', STR_PAD_LEFT);
    }

    public function feeBreakdown(): array
    {
        if ($this->purpose === 'tuition' && $this->tuitionInvoice) {
            return $this->tuitionInvoice->items ?: [['label' => 'Tuition fee', 'amount' => $this->tuitionInvoice->amount]];
        }

        return [['label' => self::LABELS[$this->purpose] ?? 'Payment fee', 'amount' => $this->amount]];
    }

    public function destination(): string
    {
        return match ($this->purpose) {
            'late_registration' => route('student.courses.registration', ['session' => $this->payable->academicSession->name, 'semester' => $this->payable->semester]),
            'tuition' => route('tuition.show', $this->tuition_invoice_id),
            'application' => $this->status === 'success' ? route('dashboard') : route('admissions.create'),
            'transcript' => route('academic.transcripts'),
            'appeal' => route('academic.appeals'),
        };
    }
}
