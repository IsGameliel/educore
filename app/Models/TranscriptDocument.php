<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranscriptDocument extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['result_versions' => 'array', 'official' => 'boolean'];

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
