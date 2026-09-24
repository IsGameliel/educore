<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_credit_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session', 9);
            $table->string('semester', 10);
            $table->unsignedInteger('credit_limit');
            $table->timestamps();
            $table->unique(['user_id', 'session', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_credit_limits');
    }
};
