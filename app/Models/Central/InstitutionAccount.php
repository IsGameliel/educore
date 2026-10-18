<?php

namespace App\Models\Central;

use Illuminate\Foundation\Auth\User as Authenticatable;

class InstitutionAccount extends Authenticatable
{
    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    public function getConnectionName()
    {
        return config('tenancy.central_connection');
    }

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
