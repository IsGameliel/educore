<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TuitionClearance extends Model
{
    use \App\Models\Concerns\BelongsToInstitution;

    protected $guarded = ['id'];
    protected $casts = ['expires_on' => 'date', 'revoked_at' => 'datetime'];
    public function approver() { return $this->belongsTo(User::class, 'approved_by')->withTrashed(); }
}
