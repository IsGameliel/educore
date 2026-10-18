<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class ResultCorrection extends Model
{
    use BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = ['proposed' => 'array'];
}
