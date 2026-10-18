<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class ResultRevision extends Model
{
    use BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = ['before' => 'array', 'after' => 'array'];

    public $timestamps = false;
}
