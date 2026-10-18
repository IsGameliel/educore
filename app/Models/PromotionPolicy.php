<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use App\Services\Tenancy\TenantState;
use Illuminate\Database\Eloquent\Model;

class PromotionPolicy extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['max_carryovers', 'updated_by'];

    protected $casts = ['max_carryovers' => 'integer'];

    public static function current(): self
    {
        return app(TenantState::class)->id() ? static::firstOrFail() : static::findOrFail(1);
    }
}
