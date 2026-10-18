<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class TestAttempt extends Model
{
    use BelongsToInstitution;

    protected $guarded = [];

    protected $hidden = ['questions', 'answers'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'expires_at' => 'datetime', 'submitted_at' => 'datetime', 'questions' => 'array', 'answers' => 'array'];
    }
}
