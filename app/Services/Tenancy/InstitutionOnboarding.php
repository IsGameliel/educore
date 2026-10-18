<?php

namespace App\Services\Tenancy;

use App\Models\Central\Institution;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InstitutionOnboarding
{
    public function required(Institution $institution): bool
    {
        return $institution->subscription_status !== 'not_required' && empty($institution->settings['onboarding_completed_at']);
    }

    public function available(Institution $institution): bool
    {
        return $institution->status === 'ready' && $institution->subscription_status !== 'awaiting_payment'
            && app(SemesterBilling::class)->accessible($institution);
    }

    public function origin(Institution $institution): string
    {
        $host = $institution->domains()->where('is_primary', true)->value('domain');
        abort_if($institution->settings['domain_pending_verification'] ?? false, 409, 'Your school address is awaiting authorization, DNS and HTTPS verification.');
        abort_unless($host, 409, 'The school portal address is not configured.');
        $platform = parse_url(config('tenancy.platform_url'));

        return (app()->environment('production') ? 'https' : ($platform['scheme'] ?? 'https')).'://'.$host.(isset($platform['port']) && ! app()->environment('production') ? ':'.$platform['port'] : '');
    }

    public function complete(Institution $institution, array $data, ?UploadedFile $logo, int $actor): void
    {
        app(TenantContext::class)->run($institution, function () use ($institution, $data, $logo, $actor) {
            $newLogo = $logo?->store('branding', 'public');
            try {
                DB::connection(config('tenancy.central_connection'))->transaction(function () use ($institution, $data, $newLogo, $actor) {
                    $school = Institution::lockForUpdate()->findOrFail($institution->id);
                    // Retried submissions cannot re-run a completed first-time setup.
                    if (! $this->required($school)) {
                        return;
                    }
                    $settings = [...($school->settings ?? []), 'primary_color' => $data['primary_color'],
                        'onboarding_completed_at' => now()->toIso8601String()];
                    if ($newLogo) {
                        $settings['logo_path'] = $newLogo;
                    }
                    $school->forceFill(['name' => $data['name'], 'settings' => $settings])->save();
                    DB::connection(config('tenancy.central_connection'))->table('institution_profiles')->where('tenant_id', $school->id)
                        ->update(['name' => $data['name'], 'updated_at' => now()]);
                    InstitutionAudit::record($school->id, 'onboarding_completed', 'billing:'.$actor,
                        ['name' => $data['name'], 'primary_color' => $data['primary_color']]);
                });
                if ($newLogo && Institution::findOrFail($institution->id)->settings['logo_path'] !== $newLogo) {
                    Storage::disk('public')->delete($newLogo);
                }
            } catch (\Throwable $exception) {
                if ($newLogo) {
                    Storage::disk('public')->delete($newLogo);
                }
                throw $exception;
            }
        });
    }

    public function issue(Institution $institution): string
    {
        abort_if($institution->settings['domain_pending_verification'] ?? false, 409, 'Your school address is awaiting authorization, DNS and HTTPS verification.');
        abort_unless($this->available($institution) && ! $this->required($institution), 403);

        return app(TenantContext::class)->run($institution, function () use ($institution) {
            $admin = User::where('email', $institution->owner_email)->where('usertype', 'admin')->first();
            abort_unless($admin, 403, 'The school administrator account is unavailable. Contact support.');
            $token = Str::random(64);
            $db = DB::connection(config('tenancy.central_connection'));
            $db->transaction(function () use ($db, $admin, $institution, $token) {
                $db->table('institution_portal_handoffs')->where('expires_at', '<=', now('UTC'))->delete();
                $db->table('institution_portal_handoffs')->insert(['token_hash' => hash('sha256', $token),
                    'institution_id' => $institution->id, 'user_id' => $admin->id,
                    'credential_fingerprint' => $this->fingerprint($admin), 'expires_at' => now('UTC')->addMinutes(2)]);
            });

            return $token;
        });
    }

    public function consume(Institution $institution, string $token): User
    {
        abort_unless($this->available($institution) && ! $this->required($institution), 403);

        return DB::connection(config('tenancy.central_connection'))->transaction(function ($db) use ($institution, $token) {
            $query = $db->table('institution_portal_handoffs')->where('token_hash', hash('sha256', $token))->where('institution_id', $institution->id);
            $handoff = $query->lockForUpdate()->first();
            abort_unless($handoff && now('UTC')->lt(Carbon::parse($handoff->expires_at, 'UTC')), 403, 'This sign-in link expired. Return to your institution account and open the portal again.');
            $admin = User::whereKey($handoff->user_id)->where('email', $institution->owner_email)->where('usertype', 'admin')->first();
            abort_unless($admin && hash_equals($handoff->credential_fingerprint, $this->fingerprint($admin)), 403);
            abort_unless($query->delete() === 1, 403);
            InstitutionAudit::record($institution->id, 'portal_handoff', 'user:'.$admin->id);

            return $admin;
        });
    }

    private function fingerprint(User $admin): string
    {
        return hash('sha256', $admin->password.'|'.$admin->two_factor_secret.'|'.$admin->two_factor_confirmed_at);
    }
}
