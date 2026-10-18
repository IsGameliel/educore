<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_portal_handoffs', function (Blueprint $table) {
            $table->string('token_hash', 64)->primary();
            $table->foreignUuid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('credential_fingerprint', 64);
            $table->dateTime('expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_portal_handoffs');
    }
};
