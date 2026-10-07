<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('registration_settings', function (Blueprint $table) {
            $table->boolean('require_late_registration_fee')->default(false);
        });
        Schema::create('late_registration_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_session_id')->constrained()->restrictOnDelete();
            $table->string('semester', 6);
            $table->string('status', 20)->default('awaiting_payment');
            $table->timestamps();
            $table->unique(['user_id', 'academic_session_id', 'semester'], 'late_registration_student_session_semester');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('late_registration_charges');
        Schema::table('registration_settings', fn (Blueprint $table) => $table->dropColumn('require_late_registration_fee'));
    }
};
