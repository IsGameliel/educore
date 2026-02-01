<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Link to the students (users)
            $table->foreignId('course_id')->constrained()->cascadeOnDelete(); // Link to the courses
            $table->string('status')->default('pending'); // Status of registration (e.g., 'completed', 'pending')
            $table->string('semester'); // Semester in which the course is registered
            $table->string('session')->nullable(); // Academic session (e.g., '2023/2024')
            $table->date('registration_date')->nullable(); // Date of registration
            $table->timestamps(); // Timestamps for created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_registrations');
    }
};
