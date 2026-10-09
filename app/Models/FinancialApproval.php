<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialApproval extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount'=>'integer', 'reviewed_at'=>'datetime'];
    public function invoice() { return $this->belongsTo(TuitionInvoice::class, 'tuition_invoice_id'); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by')->withTrashed(); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by')->withTrashed(); }
}
