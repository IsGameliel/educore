<?php

namespace App\Services\Tenancy;

use App\Models\Central\Institution;
use App\Models\Central\InstitutionDomain;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InstitutionLifecycle
{
    public function status(Institution $school, string $status, string $reason, int $actor): void
    {
        if (! in_array($status, ['ready', 'suspended', 'archived'], true) || mb_strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages(['reason' => 'Choose a valid status and a reason of at least ten characters.']);
        }
        app(InstitutionMutex::class)->run($school->id, function () use ($school, $status, $reason, $actor) {
            DB::connection(config('tenancy.central_connection'))->transaction(function () use ($school, $status, $reason, $actor) {
                $school = $school->fresh();
                if (! in_array($school->status, ['ready', 'suspended', 'archived'], true)) {
                    throw ValidationException::withMessages(['status' => 'Only provisioned institutions can change lifecycle status.']);
                }
                $before = $school->status;
                $school->forceFill(['status' => $status, 'archived_at' => $status === 'archived' ? now() : null])->save();
                $db = DB::connection(config('tenancy.central_connection'));
                if ($status !== 'ready') {
                    $db->table('institution_portal_handoffs')->where('institution_id', $school->id)->delete();
                    foreach (['institution_sessions', 'sessions', 'personal_access_tokens', 'institution_password_resets', 'jobs'] as $table) {
                        if ($db->getSchemaBuilder()->hasColumn($table, 'tenant_id')) {
                            $db->table($table)->where('tenant_id', $school->id)->delete();
                        }
                    }
                    $db->table('users')->where('tenant_id', $school->id)->update(['remember_token' => null]);
                }
                InstitutionAudit::record($school->id, 'lifecycle_changed', 'platform:'.$actor, ['from' => $before, 'to' => $status, 'reason' => $reason]);
            });
        });
    }

    public function domain(Institution $school, string $domain, string $reason, int $actor): void
    {
        $domain = InstitutionDomains::normalize($domain);
        if (strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages(['reason' => 'Explain the domain change in at least ten characters.']);
        }
        app(InstitutionMutex::class)->run($school->id, function () use ($school, $domain, $reason, $actor) {
            DB::connection(config('tenancy.central_connection'))->transaction(function () use ($school, $domain, $reason, $actor) {
                if (InstitutionDomain::where('domain', $domain)->where('institution_id', '!=', $school->id)->exists()) {
                    throw ValidationException::withMessages(['domain' => 'This domain belongs to another institution.']);
                }
                $old = $school->domains()->pluck('domain')->all();
                $school->domains()->where('domain', '!=', $domain)->delete();
                $school->domains()->updateOrCreate(['domain' => $domain], ['is_primary' => true])->forceFill(['verified_at' => now()])->save();
                $school->forceFill(['settings' => [...($school->settings ?? []), 'domain_pending_verification' => false]])->save();
                $db = DB::connection(config('tenancy.central_connection'));
                foreach (['institution_sessions', 'sessions', 'personal_access_tokens', 'institution_password_resets'] as $table) {
                    if ($db->getSchemaBuilder()->hasColumn($table, 'tenant_id')) {
                        $db->table($table)->where('tenant_id', $school->id)->delete();
                    }
                }
                $db->table('users')->where('tenant_id', $school->id)->update(['remember_token' => null]);
                $db->table('institution_portal_handoffs')->where('institution_id', $school->id)->delete();
                InstitutionAudit::record($school->id,'domain_changed','platform:'.$actor,['from' => $old, 'to' => $domain, 'reason' => $reason]);
            });
        });
    }
}
