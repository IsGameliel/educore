<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

abstract class CentralModel extends Model
{
    public function getConnectionName()
    {
        return config('tenancy.central_connection');
    }
}
