<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TuitionAdjustment extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'integer'];
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by')->withTrashed(); }
}
