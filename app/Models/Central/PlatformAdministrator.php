<?php

namespace App\Models\Central;

use Illuminate\Foundation\Auth\User as Authenticatable;

class PlatformAdministrator extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    public const ROLES = ['owner' => 'Owner', 'finance' => 'Finance', 'support' => 'Support', 'read_only' => 'Read-only'];

    public function canAccessPlatformRoute(?string $name): bool
    {
        if (str_starts_with($name ?? '', 'platform.account.') || in_array($name, ['platform.confirm', 'platform.confirm.form', 'platform.logout', 'platform.index', 'platform.institutions.index'], true)) {
            return true;
        }
        if ($this->role === 'owner') {
            return true;
        }

        return match ($name) {
            'platform.institutions.show' => in_array($this->role, ['support', 'finance', 'read_only'], true),
            'platform.institutions.status', 'platform.institutions.domain' => $this->role === 'support',
            'platform.billing', 'platform.reports' => in_array($this->role, ['finance', 'read_only'], true),
            'platform.operations' => in_array($this->role, ['support', 'read_only'], true),
            default => false,
        };
    }

    public function getConnectionName()
    {
        return config('tenancy.central_connection');
    }

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean', 'security_version' => 'integer', 'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array', 'two_factor_confirmed_at' => 'datetime'];
    }
}
