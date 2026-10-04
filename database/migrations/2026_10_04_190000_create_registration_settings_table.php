<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('registration_open')->default(true);
            $table->boolean('require_fee_clearance')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        DB::table('registration_settings')->insert([
            'id' => 1, 'registration_open' => true, 'require_fee_clearance' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_settings');
    }
};
