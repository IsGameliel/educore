<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_sessions', fn (Blueprint $table) => $table->boolean('tuition_enabled')->default(false));
        Schema::create('tuition_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('level', 3);
            $table->string('category', 12);
            $table->string('period', 10); // Annual, First, Second
            $table->json('items');
            $table->unsignedBigInteger('amount');
            $table->unsignedTinyInteger('first_percent')->default(100);
            $table->date('due_date');
            $table->date('second_due_date')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['academic_session_id', 'department_id', 'level', 'category', 'period'], 'tuition_schedule_cohort_unique');
        });
        Schema::create('tuition_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('tuition_schedule_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('student_name');
            $table->string('department_name');
            $table->string('session_name');
            $table->string('level', 3);
            $table->string('category', 12);
            $table->string('period', 10);
            $table->json('items');
            $table->unsignedBigInteger('amount');
            $table->unsignedTinyInteger('first_percent');
            $table->date('due_date');
            $table->date('second_due_date')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'academic_session_id', 'period'], 'tuition_invoice_student_period_unique');
        });
        Schema::create('tuition_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tuition_invoice_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('awaiting_payment');
            $table->timestamps();
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('tuition_invoice_id')->nullable()->constrained()->restrictOnDelete();
        });
        Schema::create('tuition_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tuition_invoice_id')->constrained()->restrictOnDelete();
            $table->string('type'); // scholarship, waiver, refund
            $table->unsignedBigInteger('amount');
            $table->text('reason');
            $table->string('external_reference')->nullable()->unique();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('tuition_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_session_id')->constrained()->restrictOnDelete();
            $table->string('semester');
            $table->text('reason');
            $table->date('expires_on');
            $table->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuition_clearances');
        Schema::dropIfExists('tuition_adjustments');
        Schema::table('payments', fn (Blueprint $table) => $table->dropConstrainedForeignId('tuition_invoice_id'));
        Schema::dropIfExists('tuition_charges');
        Schema::dropIfExists('tuition_invoices');
        Schema::dropIfExists('tuition_schedules');
        Schema::table('academic_sessions', fn (Blueprint $table) => $table->dropColumn('tuition_enabled'));
    }
};
