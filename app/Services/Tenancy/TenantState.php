<?php

namespace App\Services\Tenancy;

use RuntimeException;

class TenantState
{
    private ?string $institutionId = null;

    public function id(): ?string
    {
        return $this->institutionId;
    }

    public function enter(string $id): void
    {
        if ($this->institutionId !== null) {
            throw new RuntimeException('Nested institution context is not allowed.');
        }
        $this->institutionId = $id;
    }

    public function leave(): void
    {
        $this->institutionId = null;
    }
}
