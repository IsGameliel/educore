<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_registrations', function (Blueprint $table) {
            $table->boolean('is_carryover')->default(false)->after('previous_result_id');
        });
        DB::table('course_registrations')->whereNotNull('previous_result_id')->update(['is_carryover' => true]);
    }

    public function down(): void
    {
        Schema::table('course_registrations', function (Blueprint $table) {
            $table->dropColumn('is_carryover');
        });
    }
};
