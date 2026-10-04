<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL DDL commits immediately, so a failed migration can leave these behind.
        if (! Schema::hasColumn('payments', 'initialization_attempted_at')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->timestamp('initialization_attempted_at')->nullable();
            });
            // Older rows cannot prove that a timed-out initialization never reached Paystack.
            \Illuminate\Support\Facades\DB::table('payments')->update([
                'initialization_attempted_at'=>now(),
            ]);
        }
        if (! Schema::hasIndex('payments', 'payments_status_created_at_index')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index(['status', 'created_at']);
            });
        }
        if (! Schema::hasTable('financial_approvals')) {
        Schema::create('financial_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tuition_invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('type');
            $table->unsignedBigInteger('amount');
            $table->text('reason');
            $table->text('review_reason')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
        }
        if (! Schema::hasTable('payment_settlements')) {
        Schema::create('payment_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_id')->unique();
            $table->string('domain');
            $table->string('currency', 3);
            $table->string('status');
            $table->unsignedBigInteger('gross');
            $table->unsignedBigInteger('fees');
            $table->unsignedBigInteger('net');
            $table->unsignedBigInteger('matched_amount');
            $table->unsignedInteger('unmatched_count');
            $table->json('exceptions');
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('checked_at')->useCurrent();
            $table->timestamps();
        });
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('payment_settlements');
        Schema::dropIfExists('financial_approvals');
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn('initialization_attempted_at');
        });
    }
};
