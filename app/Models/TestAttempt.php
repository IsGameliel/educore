<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestAttempt extends Model
{
    protected $guarded = [];

    protected $hidden = ['questions', 'answers'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'expires_at' => 'datetime', 'submitted_at' => 'datetime', 'questions' => 'array', 'answers' => 'array'];
    }
}
