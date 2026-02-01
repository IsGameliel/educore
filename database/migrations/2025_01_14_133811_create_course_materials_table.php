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
        Schema::create('course_materials', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('level');
            $table->string('semester');
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete(); // Add the department_id column as a foreign key
            $table->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('cover_photo')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_materials');
    }
};
