<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use BelongsToInstitution;

    protected $fillable = [
        'title',
    ];
}
