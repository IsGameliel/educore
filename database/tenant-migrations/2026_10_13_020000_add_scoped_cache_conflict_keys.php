<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['cache', 'cache_locks'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique(['tenant_id', 'key']));
        }
    }

    public function down(): void
    {
        foreach (['cache', 'cache_locks'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique(['tenant_id', 'key']));
        }
    }
};
