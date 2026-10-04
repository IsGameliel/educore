<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tuition_schedules', function (Blueprint $table) {
            $table->softDeletes();
            // NULL on deleted rows permits a replacement schedule for the same cohort.
            $table->unsignedTinyInteger('active_slot')->nullable()->default(1);
            $table->unique(['academic_session_id', 'department_id', 'level', 'category', 'period', 'active_slot'], 'tuition_schedule_active_cohort_unique');
        });
        Schema::table('tuition_schedules', fn (Blueprint $table) => $table->dropUnique('tuition_schedule_cohort_unique'));
    }

    public function down(): void
    {
        if (DB::table('tuition_schedules')->select('academic_session_id', 'department_id', 'level', 'category', 'period')
            ->groupBy('academic_session_id', 'department_id', 'level', 'category', 'period')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Cannot roll back while replacement schedules exist. Preserve and reconcile the historical records first.');
        }
        Schema::table('tuition_schedules', function (Blueprint $table) {
            $table->unique(['academic_session_id', 'department_id', 'level', 'category', 'period'], 'tuition_schedule_cohort_unique');
        });
        Schema::table('tuition_schedules', function (Blueprint $table) {
            $table->dropUnique('tuition_schedule_active_cohort_unique');
            $table->dropColumn(['deleted_at', 'active_slot']);
        });
    }
};
