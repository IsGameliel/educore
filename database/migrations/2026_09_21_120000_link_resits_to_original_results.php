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
            $table->foreignId('resit_of_result_id')->nullable()->constrained('results')->restrictOnDelete();
            $table->foreignId('resit_authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resit_authorized_at')->nullable();
        });

        // Link existing resits only when the original exam can be identified unambiguously.
        // Preserve every score and do not invent authorization details for older attempts.
        DB::table('results')->where('attempt_type', 'resit')->orderBy('id')->chunkById(100, function ($resits) {
            foreach ($resits as $resit) {
                $originals = DB::table('results')->where('user_id', $resit->user_id)
                    ->where('department_id', $resit->department_id)->where('session', $resit->session)
                    ->where('semester', $resit->semester)->where('course_code', $resit->course_code)
                    ->whereIn('attempt_type', ['regular', 'repeat'])->pluck('id');
                if ($originals->count() === 1) {
                    DB::table('results')->where('id', $resit->id)->update(['resit_of_result_id' => $originals->first()]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropForeign(['resit_of_result_id']);
            $table->dropForeign(['resit_authorized_by']);
            $table->dropColumn(['resit_of_result_id', 'resit_authorized_by', 'resit_authorized_at']);
        });
    }
};
