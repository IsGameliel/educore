<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranscriptRequest extends Model
{
    public function payment()
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    protected $guarded = ['id'];

    protected $casts = [];
}
