<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TuitionSchedule extends Model
{
    use SoftDeletes;

    public function invoices() { return $this->hasMany(TuitionInvoice::class); }

    protected $guarded = ['id'];
    protected $casts = ['items' => 'array', 'amount' => 'integer', 'first_percent' => 'integer', 'due_date' => 'date', 'second_due_date' => 'date', 'published_at' => 'datetime'];

    public function academicSession() { return $this->belongsTo(AcademicSession::class); }
    public function department() { return $this->belongsTo(Department::class); }
}
