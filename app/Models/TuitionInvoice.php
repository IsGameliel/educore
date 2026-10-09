<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TuitionInvoice extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['items' => 'array', 'amount' => 'integer', 'first_percent' => 'integer', 'due_date' => 'date', 'second_due_date' => 'date', 'cancelled_at'=>'datetime', 'resumed_at'=>'datetime', 'withdrawal_reviewed_at'=>'datetime'];

    public function user() { return $this->belongsTo(User::class)->withTrashed(); }
    public function schedule() { return $this->belongsTo(TuitionSchedule::class, 'tuition_schedule_id'); }
    public function scopeCurrent($query) { return $query->whereNull('cancelled_at'); }
    public function scopeActive($query) { return $query->current()->where(fn ($q) => $q->whereHas('schedule')->orWhereNotNull('resumed_at')); }
    public function isActive(): bool { return ! $this->cancelled_at && ($this->resumed_at || ($this->relationLoaded('schedule') ? $this->schedule !== null : $this->schedule()->exists())); }
    public function needsWithdrawalReview(): bool { return ! $this->cancelled_at && ! $this->resumed_at && ! $this->isActive() && (! $this->withdrawal_reviewed_at || $this->totals()['balance'] > 0); }
    public function replacement() { return $this->belongsTo(self::class, 'replacement_invoice_id'); }
    public function reminders() { return $this->hasMany(TuitionReminder::class); }
    public function academicSession() { return $this->belongsTo(AcademicSession::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function adjustments() { return $this->hasMany(TuitionAdjustment::class); }
    public function charges() { return $this->hasMany(TuitionCharge::class); }

    public function totals(): array
    {
        $this->loadMissing(['payments', 'adjustments']);
        $credits = (int) $this->adjustments->whereIn('type', ['scholarship', 'waiver'])->sum('amount');
        // Linked legacy refunds are already included in the verified gateway deduction.
        $legacyRefunds = (int) $this->adjustments->where('type', 'refund')->whereNull('gateway_change_id')->sum('amount');
        $gatewayRefunds = (int) $this->payments->where('status', 'success')->sum('gateway_deduction');
        $refunds = $legacyRefunds + $gatewayRefunds;
        $received = (int) $this->payments->where('status', 'success')->sum('amount');
        $due = $this->cancelled_at ? 0 : max(0, $this->amount - $credits);
        $paid = max(0, $received - $refunds);

        return ['gross' => $this->amount, 'credits' => $credits, 'refunds' => $refunds, 'due' => $due, 'paid' => $paid,
            'dispute_open' => $this->payments->contains('dispute_open', true),
            'refund_review' => $legacyRefunds > 0 && $gatewayRefunds > 0,
            'reversal_review' => $this->payments->contains(fn ($payment) => $payment->status === 'success' && $payment->gateway_reversed && $payment->gateway_deduction === 0),
            'balance' => max(0, $due - $paid), 'overpayment' => max(0, $paid - $due),
            'status' => $this->cancelled_at ? 'cancelled' : ($paid >= $due ? 'paid' : ($paid > 0 ? 'partially_paid' : 'unpaid'))];
    }

    public function requiredAmount(string $semester): int
    {
        $due = $this->totals()['due'];

        return $this->period === 'Annual' && $semester === 'First'
            ? intdiv($due * $this->first_percent + 99, 100) : $due;
    }

    public function overdue(): bool
    {
        if (! $this->isActive()) { return false; }
        $totals = $this->totals();
        return ($this->due_date->lt(today()) && $totals['paid'] < $this->requiredAmount('First'))
            || ($this->second_due_date?->lt(today()) && $totals['balance'] > 0);
    }
}
