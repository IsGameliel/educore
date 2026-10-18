<?php

namespace App\Services\Tenancy;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlatformAudit
{
    public static function record(string $event, ?string $subject = null, array $details = [], ?int $actor = null): void
    {
        DB::connection(config('tenancy.central_connection'))->table('platform_audit_events')->insert([
            'actor_id' => $actor ?? Auth::guard('platform')->id(), 'event' => $event,
            'subject' => $subject, 'details' => json_encode($details, JSON_THROW_ON_ERROR),
            'ip_address' => app()->runningInConsole() ? null : request()->ip(), 'created_at' => now(),
        ]);
    }
}
