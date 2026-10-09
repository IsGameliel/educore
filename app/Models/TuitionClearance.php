<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TuitionClearance extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['expires_on' => 'date', 'revoked_at' => 'datetime'];
    public function approver() { return $this->belongsTo(User::class, 'approved_by')->withTrashed(); }
}
