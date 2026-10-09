<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_academic_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->string('level', 3);
            $table->timestamps();
            $table->unique(['user_id', 'academic_session_id']);
        });
        // Snapshot current enrollment only; historical levels require staff confirmation.
        $session = \Illuminate\Support\Facades\DB::table('academic_sessions')->where('is_active', true)->orderByDesc('start_year')->first();
        if ($session) {
            \Illuminate\Support\Facades\DB::table('users')->where('usertype', 'student')
                ->whereIn('level', ['100', '200', '300', '400', '500', '600'])->orderBy('id')->chunkById(200, function ($students) use ($session) {
                    \Illuminate\Support\Facades\DB::table('student_academic_sessions')->insert($students->map(fn ($student) => [
                        'user_id' => $student->id, 'academic_session_id' => $session->id, 'level' => (string) $student->level,
                        'created_at' => now(), 'updated_at' => now(),
                    ])->all());
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_academic_sessions');
    }
};
