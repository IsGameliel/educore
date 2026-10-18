<?php

namespace App\Models\Central;

class InstitutionIntegration extends CentralModel
{
    protected $primaryKey = 'institution_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return ['credentials' => 'encrypted:array'];
    }
}
