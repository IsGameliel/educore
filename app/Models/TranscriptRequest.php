<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranscriptRequest extends Model
{
    use \App\Models\Concerns\BelongsToInstitution;

    public function payment()
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    protected $guarded = ['id'];

    protected $casts = [];
}
