<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_administrators', function (Blueprint $table) {
            $table->string('role')->default('read_only');
            $table->unsignedInteger('security_version')->default(1);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
        });
        // Existing administrators retain their established authority. New accounts default to read-only.
        DB::table('platform_administrators')->update(['role' => 'owner']);
        Schema::create('platform_account_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('token', 64);
            $table->string('purpose');
            $table->dateTime('expires_at');
            $table->dateTime('created_at');
        });
        Schema::create('platform_login_sessions', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->unsignedBigInteger('administrator_id')->index();
            $table->unsignedInteger('security_version');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('last_seen_at');
        });
        Schema::create('platform_audit_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('event')->index();
            $table->string('subject')->nullable()->index();
            $table->json('details');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        foreach (['platform_audit_events', 'platform_login_sessions', 'platform_account_tokens'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('platform_administrators', fn (Blueprint $table) => $table->dropColumn(['role', 'security_version', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']));
    }
};
