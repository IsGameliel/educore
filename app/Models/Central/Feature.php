<?php

namespace App\Models\Central;

class Feature extends CentralModel
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
