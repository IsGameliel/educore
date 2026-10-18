<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class PaymentGatewayChange extends Model
{
    use BelongsToInstitution;

    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $casts = ['amount' => 'integer', 'gateway_updated_at' => 'datetime'];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
