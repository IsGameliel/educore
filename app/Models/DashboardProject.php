<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class DashboardProject extends Model
{
    use BelongsToInstitution;

    protected $fillable = [
        'user_id',
        'name',
        'due_date',
        'progress',
    ];

    protected $casts = [
        'due_date' => 'date',
        'progress' => 'integer',
    ];
}
