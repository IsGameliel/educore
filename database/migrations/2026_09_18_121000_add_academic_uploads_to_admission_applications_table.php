<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            $table->json('olevel_results')->nullable()->after('qualification');
            $table->string('jamb_result_path')->nullable()->after('academic_notes');
            $table->string('diploma_result_path')->nullable()->after('jamb_result_path');
            $table->string('transcript_path')->nullable()->after('diploma_result_path');
        });
    }

    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            $table->dropColumn([
                'olevel_results',
                'jamb_result_path',
                'diploma_result_path',
                'transcript_path',
            ]);
        });
    }
};
