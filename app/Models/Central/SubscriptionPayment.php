<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SubscriptionPayment extends CentralModel
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }
}
