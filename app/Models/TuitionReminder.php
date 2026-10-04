<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TuitionReminder extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['queued_at'=>'datetime', 'sent_at'=>'datetime'];
    public function invoice() { return $this->belongsTo(TuitionInvoice::class, 'tuition_invoice_id'); }
    public function label(): string
    {
        return str_contains($this->notice_key, '_upcoming_') ? 'Upcoming payment' : (str_contains($this->notice_key, '_due_today_') ? 'Payment due today' : 'Overdue payment');
    }
}
