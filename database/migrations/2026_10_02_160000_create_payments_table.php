<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->morphs('payable');
            $table->unique(['payable_type', 'payable_id']);
            $table->string('purpose', 30)->index();
            $table->string('reference', 80)->unique();
            $table->string('email');
            $table->unsignedBigInteger('amount'); // Kobo, never floating point.
            $table->string('currency', 3)->default('NGN');
            $table->string('status', 20)->default('pending')->index();
            $table->text('authorization_url')->nullable();
            $table->string('gateway_id')->nullable()->unique();
            $table->string('channel')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
