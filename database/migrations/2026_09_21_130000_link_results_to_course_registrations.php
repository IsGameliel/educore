<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->foreignId('course_registration_id')->nullable()->constrained('course_registrations')->restrictOnDelete();
        });

        $this->backfill();
    }

    public function backfill(): void
    {
        // Link only unambiguous historical matches, without changing marks or versions.
        DB::table('results')->orderBy('id')->chunkById(200, function ($results) {
            foreach ($results as $result) {
                $ids = DB::table('course_registrations as registrations')
                    ->join('courses', 'courses.id', '=', 'registrations.course_id')
                    ->join('academic_sessions', 'academic_sessions.id', '=', 'courses.academic_session_id')
                    ->where('registrations.user_id', $result->user_id)
                    ->where('registrations.session', $result->session)->where('academic_sessions.name', $result->session)
                    ->where('registrations.semester', $result->semester)->where('courses.semester', $result->semester)
                    ->where('courses.department_id', $result->department_id)->where('courses.code', $result->course_code)
                    ->pluck('registrations.id');
                if ($ids->count() === 1) {
                    DB::table('results')->where('id', $result->id)->update(['course_registration_id' => $ids->first()]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('results', fn (Blueprint $table) => $table->dropConstrainedForeignId('course_registration_id'));
    }
};
