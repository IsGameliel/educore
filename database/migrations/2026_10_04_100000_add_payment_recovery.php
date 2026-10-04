<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('reconciliation_attempted_at')->nullable()->index();
            $table->timestamp('financial_checked_at')->nullable();
            $table->timestamp('financial_attempted_at')->nullable()->index();
            $table->boolean('gateway_reversed')->default(false);
            $table->unsignedBigInteger('gateway_deduction')->default(0);
            $table->boolean('dispute_open')->default(false);
        });
        Schema::create('payment_gateway_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->string('kind', 10);
            $table->string('gateway_id', 80);
            $table->string('status', 60);
            $table->string('resolution', 60)->nullable();
            $table->string('dispute_id', 80)->nullable();
            $table->unsignedBigInteger('amount');
            $table->timestamp('gateway_updated_at', 6)->nullable();
            $table->timestamps();
            $table->unique(['kind', 'gateway_id']);
        });
        Schema::table('tuition_adjustments', function (Blueprint $table) {
            $table->foreignId('gateway_change_id')->nullable()->unique()->constrained('payment_gateway_changes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tuition_adjustments', fn (Blueprint $table) => $table->dropConstrainedForeignId('gateway_change_id'));
        Schema::dropIfExists('payment_gateway_changes');
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn([
            'reconciliation_attempted_at', 'financial_checked_at', 'financial_attempted_at',
            'gateway_reversed', 'gateway_deduction', 'dispute_open',
        ]));
    }
};
