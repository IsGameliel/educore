<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardProject extends Model
{
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
