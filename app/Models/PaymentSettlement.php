<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class PaymentSettlement extends Model
{
    use BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = ['exceptions' => 'array', 'settled_at' => 'datetime', 'checked_at' => 'datetime'];
}
