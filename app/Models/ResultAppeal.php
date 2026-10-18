<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class ResultAppeal extends Model
{
    use BelongsToInstitution;

    public function payment()
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    protected $guarded = ['id'];

    protected $casts = [];
}
