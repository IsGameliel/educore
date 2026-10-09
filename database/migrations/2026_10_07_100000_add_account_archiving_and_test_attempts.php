<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('responses')->select('test_id', 'student_id')->groupBy('test_id', 'student_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Duplicate test responses exist. Review and resolve them before migrating; no historical scores were removed.');
        }
        // MySQL DDL is not transactional; allow a failed deployment to resume.
        if (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        }
        if (! Schema::hasIndex('responses', 'responses_test_id_student_id_unique')) {
            Schema::table('responses', fn (Blueprint $table) => $table->unique(['test_id', 'student_id']));
        }
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('expires_at');
            $table->json('questions');
            $table->json('answers');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['test_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_attempts');
        Schema::table('responses', fn (Blueprint $table) => $table->dropUnique(['test_id', 'student_id']));
        Schema::table('users', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
