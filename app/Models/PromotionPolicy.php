<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionPolicy extends Model
{
    protected $fillable = ['max_carryovers', 'updated_by'];

    protected $casts = ['max_carryovers' => 'integer'];

    public static function current(): self
    {
        return static::findOrFail(1);
    }
}
