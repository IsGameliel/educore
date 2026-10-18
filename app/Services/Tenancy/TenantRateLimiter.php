<?php

namespace App\Services\Tenancy;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository;

class TenantRateLimiter extends RateLimiter
{
    public function useCache(Repository $cache): void
    {
        $this->cache = $cache;
    }
}
