<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class NameChangeRequest extends Model
{
    use BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
