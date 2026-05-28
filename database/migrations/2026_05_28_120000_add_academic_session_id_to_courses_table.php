<?php

use App\Models\AcademicSession;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('academic_session_id')
                ->nullable()
                ->after('level')
                ->constrained('academic_sessions')
                ->nullOnDelete();

            $table->index(['academic_session_id', 'department_id', 'level', 'semester'], 'courses_session_department_level_semester_index');
        });

        $defaultSessionId = AcademicSession::current()?->id
            ?? AcademicSession::query()->orderByDesc('start_year')->value('id');

        if ($defaultSessionId) {
            DB::table('courses')
                ->whereNull('academic_session_id')
                ->update(['academic_session_id' => $defaultSessionId]);
        }
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('courses_session_department_level_semester_index');
            $table->dropConstrainedForeignId('academic_session_id');
        });
    }
};
