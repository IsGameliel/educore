<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('operation_health', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->string('status');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('message')->nullable();
            $table->timestamps();
        });
        Schema::create('student_progressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('from_session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->foreignId('to_session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->string('from_level');
            $table->string('to_level');
            $table->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'to_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_progressions');
        Schema::dropIfExists('operation_health');
    }
};
