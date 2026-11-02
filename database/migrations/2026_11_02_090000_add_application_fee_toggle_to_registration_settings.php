<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_settings', function (Blueprint $table) {
            $table->boolean('application_fee_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('registration_settings', function (Blueprint $table) {
            $table->dropColumn('application_fee_enabled');
        });
    }
};
