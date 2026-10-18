<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InstitutionExport extends CentralModel
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'bytes' => 'integer'];
    }
}
