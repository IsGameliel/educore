<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tuition_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('period', 10);
            $table->json('items');
            $table->unsignedBigInteger('amount');
            $table->unsignedTinyInteger('first_percent')->default(100);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::table('tuition_invoices', function (Blueprint $table) {
            $table->unsignedTinyInteger('active_slot')->nullable()->default(1);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->timestamp('withdrawal_reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('review_reason')->nullable();
            $table->foreignId('replacement_invoice_id')->nullable()->constrained('tuition_invoices')->restrictOnDelete();
            $table->unique(['user_id','academic_session_id','period','active_slot'], 'tuition_invoice_current_period_unique');
        });
        Schema::table('tuition_invoices', fn (Blueprint $table) => $table->dropUnique('tuition_invoice_student_period_unique'));
        Schema::create('tuition_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tuition_invoice_id')->constrained()->restrictOnDelete();
            $table->string('notice_key', 100);
            $table->string('status')->default('pending');
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['tuition_invoice_id','notice_key']);
        });
    }

    public function down(): void
    {
        if (DB::table('tuition_invoices')->select('user_id','academic_session_id','period')->groupBy('user_id','academic_session_id','period')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Replacement invoices exist. Preserve their history before rolling back.');
        }
        Schema::dropIfExists('tuition_reminders');
        Schema::table('tuition_invoices', fn (Blueprint $table) => $table->unique(['user_id','academic_session_id','period'], 'tuition_invoice_student_period_unique'));
        Schema::table('tuition_invoices', function (Blueprint $table) {
            $table->dropUnique('tuition_invoice_current_period_unique');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('replacement_invoice_id');
            $table->dropColumn(['active_slot','cancelled_at','resumed_at','withdrawal_reviewed_at','review_reason']);
        });
        Schema::dropIfExists('tuition_templates');
    }
};
