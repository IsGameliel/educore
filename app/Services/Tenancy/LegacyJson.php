<?php

namespace App\Services\Tenancy;

class LegacyJson
{
    public static function array(mixed $value): array
    {
        for ($i = 0; $i < 3 && is_string($value); $i++) {
            $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }
        if (! is_array($value)) {
            throw new \RuntimeException('Legacy JSON must decode to an array or object.');
        }

return $value;
    }
}
