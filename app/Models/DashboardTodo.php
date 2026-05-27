<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardTodo extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'completed',
    ];

    protected $casts = [
        'completed' => 'boolean',
    ];
}
