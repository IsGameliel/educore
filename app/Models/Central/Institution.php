<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends CentralModel
{
    use HasUuids;

    protected $fillable = ['slug', 'name', 'plan_code', 'owner_name', 'owner_email', 'timezone'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'provisioned_at' => 'datetime'];
    }

    public function domains(): HasMany
    {
        return $this->hasMany(InstitutionDomain::class);
    }
}
