<?php

namespace App\Models\Central;

class InstitutionDatabaseCopy extends CentralModel
{
    protected $table = 'institution_database_copies';

    protected $primaryKey = 'institution_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'encrypted', 'enabled' => 'boolean', 'manifest' => 'array',
            'queued_at' => 'datetime', 'last_attempt_at' => 'datetime', 'last_synced_at' => 'datetime'];
    }
}
