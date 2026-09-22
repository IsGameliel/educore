<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultRevision extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['before' => 'array', 'after' => 'array'];

    public $timestamps = false;
}
