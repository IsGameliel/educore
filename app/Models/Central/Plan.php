<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plan extends CentralModel
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['standard_rate_minor' => 'integer', 'promotional_rate_minor' => 'integer', 'promotion_semesters' => 'integer', 'signup_enabled' => 'boolean'];
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_features', 'plan_code', 'feature_code', 'code', 'code');
    }
}
