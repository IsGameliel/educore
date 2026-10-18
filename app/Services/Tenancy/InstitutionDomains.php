<?php

namespace App\Services\Tenancy;

use Illuminate\Validation\ValidationException;

class InstitutionDomains
{
    public static function normalize(string $domain): string
    {
        $domain = strtolower(rtrim(trim($domain), '.'));
        $base = strtolower(config('tenancy.institution_domain'));
        $reserved = array_map('strtolower', [...config('tenancy.central_domains'), ...config('tenancy.legacy_hosts'), $base]);
        $valid = strlen($domain) <= 253 && str_contains($domain, '.')
            && ! filter_var($domain, FILTER_VALIDATE_IP)
            && filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            && ! in_array($domain, $reserved, true);
        if (str_ends_with($domain, '.'.$base)) {
            $label = substr($domain, 0, -strlen('.'.$base));
            $valid = $valid && preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $label)
                && strlen($label) <= 63 && ! in_array($label, ['www', 'mail', 'admin', 'platform', 'dashboard', 'api'], true);
        }
        if (! $valid) {
            throw ValidationException::withMessages(['domain' => 'Enter an available school hostname, such as academics.mudiameuniversity.edu.ng, without a scheme, port or path. Central platform addresses cannot be assigned to a school.']);
        }

        return $domain;
    }
}
