<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use App\Services\Tenancy\TenantState;
use Illuminate\Database\Eloquent\Model;

class RegistrationSetting extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['registration_open', 'require_fee_clearance', 'require_late_registration_fee', 'updated_by'];

    protected $casts = ['registration_open' => 'boolean', 'require_fee_clearance' => 'boolean', 'require_late_registration_fee' => 'boolean'];

    public static function current(): self
    {
        return app(TenantState::class)->id() ? static::firstOrFail() : static::findOrFail(1);
    }
}
