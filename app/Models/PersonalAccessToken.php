<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;

class PersonalAccessToken extends \Laravel\Sanctum\PersonalAccessToken
{
    use BelongsToInstitution;
}
