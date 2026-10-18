<?php

namespace App\Services\Tenancy;

use App\Models\Central\Institution;
use App\Models\Central\InstitutionIntegration;
use Illuminate\Support\Facades\DB;

class InstitutionIntegrations
{
    public function key(?string $id = null): ?string
    {
        $id ??= app(TenantState::class)->id();

        return $id ? (InstitutionIntegration::find($id)?->credentials['paystack_secret_key'] ?? null) : config('services.paystack.secret_key');
    }

    public function save(Institution $institution, string $key, int $actor): void
    {
        if (! preg_match('/\Ask_(test|live)_[A-Za-z0-9_\-]{6,200}\z/D', $key)) {
            throw new \InvalidArgumentException('Enter a valid Paystack secret key.');
        }
        app(InstitutionMutex::class)->run($institution->id, function () use ($institution, $key, $actor) {
            DB::connection(config('tenancy.central_connection'))->transaction(function () use ($institution, $key, $actor) {
                $record = InstitutionIntegration::firstOrNew(['institution_id' => $institution->id]);
                $credentials = $record->credentials ?? [];
                $old = $credentials['paystack_secret_key'] ?? null;
                $history = array_values(array_filter($credentials['previous_keys'] ?? [], fn ($item) => ($item['expires_at'] ?? 0) > time()));
                if ($old && $old !== $key) {
                    array_unshift($history, ['key' => $old, 'expires_at' => time() + 7 * 86400]);
                }
                $record->forceFill(['credentials' => ['paystack_secret_key' => $key, 'previous_keys' => array_slice($history, 0, 2)], 'updated_by' => $actor])->save();
                InstitutionAudit::record($institution->id, 'integration_key_updated', 'school:'.$actor, ['provider' => 'paystack', 'mode' => str_starts_with($key, 'sk_live_') ? 'live' : 'test']);
            });
        });
    }

    public function signatureValid(string $body, string $signature): bool
    {
        $id = app(TenantState::class)->id();
        if (! $id) {
            $key = config('services.paystack.secret_key');

            return $key && hash_equals(hash_hmac('sha512', $body, $key), $signature);
        }
        $credentials = InstitutionIntegration::find($id)?->credentials ?? [];
        $keys = array_filter([$credentials['paystack_secret_key'] ?? null]);
        foreach ($credentials['previous_keys'] ?? [] as $previous) {
            if (($previous['expires_at'] ?? 0) > time()) {
                $keys[] = $previous['key'];
            }
        }
        foreach ($keys as $key) {
            if (hash_equals(hash_hmac('sha512', $body, $key), $signature)) {
                return true;
            }
        }

        return false;
    }

    public function financialEvent(callable $callback): mixed
    {
        $previous = config('tenancy.financial_event', false);
        config(['tenancy.financial_event' => true]);
        try {
            return $callback();
        } finally {
            config(['tenancy.financial_event' => $previous]);
        }
    }
}
