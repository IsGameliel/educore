<?php

use App\Services\Tenancy\LaunchCatalogue;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(LaunchCatalogue::class)->apply();
    }

    public function down(): void
    {
        throw new RuntimeException('Catalogue availability changes require a reviewed forward migration.');
    }
};
