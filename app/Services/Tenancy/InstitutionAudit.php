<?php

namespace App\Services\Tenancy;

use Illuminate\Support\Facades\DB;

class InstitutionAudit
{
    public static function record(string $id, string $event, ?string $actor, array $details = []): void
    {
        DB::connection(config('tenancy.central_connection'))->table('institution_events')->insert([
            'institution_id' => $id, 'event' => $event, 'actor' => $actor, 'details' => json_encode($details, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }
}
