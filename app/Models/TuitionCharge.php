<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TuitionCharge extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'integer'];
    public function invoice() { return $this->belongsTo(TuitionInvoice::class, 'tuition_invoice_id'); }
    public function payment() { return $this->morphOne(Payment::class, 'payable'); }
}
