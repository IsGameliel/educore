<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Laravel\Jetstream\Membership as JetstreamMembership;

class Membership extends JetstreamMembership
{
    use BelongsToInstitution;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;
}
