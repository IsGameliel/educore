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
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Changed to user_id
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->string('matric_number');
            $table->string('session');
            $table->string('semester');
            $table->string('level');
            $table->string('course_code');
            $table->string('course_title');
            $table->unsignedTinyInteger('credit_unit');
            $table->decimal('score', 5, 2);
            $table->string('grade')->nullable();
            $table->decimal('grade_point', 3, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
