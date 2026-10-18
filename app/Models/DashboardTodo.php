<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class DashboardTodo extends Model
{
    use BelongsToInstitution;

    protected $fillable = [
        'user_id',
        'title',
        'completed',
    ];

    protected $casts = [
        'completed' => 'boolean',
    ];
}
