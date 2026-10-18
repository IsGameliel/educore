<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class Responses extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['test_id', 'student_id', 'answers', 'score'];

    protected $casts = [
        'answers' => 'array',
    ];

    public function test()
    {
        return $this->belongsTo(Tests::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id')->withTrashed();
    }
}
