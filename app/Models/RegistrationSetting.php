<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationSetting extends Model
{
    protected $fillable = ['registration_open', 'require_fee_clearance', 'require_late_registration_fee', 'updated_by'];

    protected $casts = ['registration_open' => 'boolean', 'require_fee_clearance' => 'boolean', 'require_late_registration_fee' => 'boolean'];

    public static function current(): self
    {
        return static::findOrFail(1);
    }
}
