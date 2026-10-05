<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('merged_into_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('merged_at')->nullable();
        });
        Schema::create('student_account_merges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retained_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->json('account_snapshots');
            $table->json('record_changes');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::table('student_account_merges')->exists()) {
            throw new RuntimeException('Account merges exist. Restore a pre-merge backup before rolling back this migration.');
        }
        Schema::dropIfExists('student_account_merges');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('merged_into_id');
            $table->dropColumn('merged_at');
        });
    }
};
