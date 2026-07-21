<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_sessions', 'scan_token')) {
                $table->string('scan_token', 80)->nullable()->unique()->after('taken_by');
            }

            if (!Schema::hasColumn('attendance_sessions', 'scan_expires_at')) {
                $table->timestamp('scan_expires_at')->nullable()->after('scan_token');
            }

            if (!Schema::hasColumn('attendance_sessions', 'scan_code_sent_at')) {
                $table->timestamp('scan_code_sent_at')->nullable()->after('scan_expires_at');
            }
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_records', 'registered_at')) {
                $table->timestamp('registered_at')->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_records', 'registered_at')) {
                $table->dropColumn('registered_at');
            }
        });

        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_sessions', 'scan_code_sent_at')) {
                $table->dropColumn('scan_code_sent_at');
            }

            if (Schema::hasColumn('attendance_sessions', 'scan_expires_at')) {
                $table->dropColumn('scan_expires_at');
            }

            if (Schema::hasColumn('attendance_sessions', 'scan_token')) {
                $table->dropUnique(['scan_token']);
                $table->dropColumn('scan_token');
            }
        });
    }
};
