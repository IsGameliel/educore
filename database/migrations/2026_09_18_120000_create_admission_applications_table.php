<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('application_number')->unique();
            $table->string('status')->default('completed');
            $table->string('surname');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->date('date_of_birth');
            $table->string('gender');
            $table->string('phone');
            $table->string('address');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('parent_name');
            $table->string('parent_relationship');
            $table->string('parent_phone');
            $table->string('parent_email')->nullable();
            $table->string('parent_address')->nullable();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('level');
            $table->year('entry_year');
            $table->string('previous_school');
            $table->string('qualification');
            $table->string('graduation_year')->nullable();
            $table->text('academic_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_applications');
    }
};
