<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_credit_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('semester', 10);
            $table->unsignedInteger('credit_limit');
            $table->timestamps();
            $table->unique(['department_id', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_credit_limits');
    }
};
