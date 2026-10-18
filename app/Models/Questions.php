<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class Questions extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['test_id', 'question_text', 'options', 'correct_option', 'marks'];

    protected $casts = [
        'options' => 'array',
    ];

    public function test()
    {
        return $this->belongsTo(Tests::class);
    }
}
