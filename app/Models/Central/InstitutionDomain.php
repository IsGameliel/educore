<?php

namespace App\Models\Central;

class InstitutionDomain extends CentralModel
{
    protected $fillable = ['domain', 'is_primary'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'verified_at' => 'datetime'];
    }
}
