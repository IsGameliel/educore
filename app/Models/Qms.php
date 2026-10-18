<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class Qms extends Model
{
    use BelongsToInstitution;

    protected $fillable = [
        'title',
        'designation_id',
        'file_path',
    ];
}
