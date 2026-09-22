<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranscriptDocument extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['result_versions' => 'array', 'official' => 'boolean'];
}
