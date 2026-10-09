<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NameChangeRequest extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['reviewed_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class)->withTrashed(); }
}
