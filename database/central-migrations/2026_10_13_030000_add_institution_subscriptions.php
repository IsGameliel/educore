<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('subscription_status')->default('not_required')->index();
        });
        Schema::create('institution_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('institution_id')->unique()->constrained('institutions')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->string('plan_code', 32);
            $table->foreign('plan_code')->references('code')->on('plans')->restrictOnDelete();
            $table->string('plan_name');
            $table->json('feature_snapshot');
            $table->string('semester_name');
            $table->date('semester_start');
            $table->unsignedInteger('student_count');
            $table->unsignedBigInteger('rate_minor');
            $table->unsignedBigInteger('standard_rate_minor');
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('NGN');
            $table->boolean('promotional')->default(true);
            $table->string('status')->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['institution_id', 'semester_start']);
        });
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('subscription_invoices')->restrictOnDelete();
            $table->string('reference')->unique();
            $table->string('email');
            $table->string('status')->default('initializing')->index();
            $table->text('checkout_url')->nullable();
            $table->string('gateway_id')->nullable()->unique();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('institution_accounts');
        Schema::table('institutions', fn (Blueprint $table) => $table->dropColumn('subscription_status'));
    }
};
