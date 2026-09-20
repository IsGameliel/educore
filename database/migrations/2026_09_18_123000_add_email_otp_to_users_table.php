<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_otp_hash')->nullable();
            $table->string('email_otp_address')->nullable();
            $table->timestamp('email_otp_expires_at')->nullable();
            $table->timestamp('email_otp_sent_at')->nullable();
            $table->unsignedTinyInteger('email_otp_attempts')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_otp_hash', 'email_otp_address', 'email_otp_expires_at', 'email_otp_sent_at', 'email_otp_attempts']);
        });
    }
};
