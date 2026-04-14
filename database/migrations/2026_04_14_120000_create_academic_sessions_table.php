<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 9)->unique();
            $table->unsignedSmallInteger('start_year');
            $table->unsignedSmallInteger('end_year');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_sessions');
    }
};
