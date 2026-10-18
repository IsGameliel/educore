<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            // Existing invoices retain their previously agreed open-ended access.
            $table->date('semester_end')->nullable();
            $table->string('price_version', 64)->nullable();
            $table->string('billing_policy')->default('semester-fixed-v1');
        });
        Schema::create('institution_feature_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->string('feature_code');
            $table->boolean('allowed');
            $table->text('reason');
            $table->unsignedBigInteger('administrator_id');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['institution_id', 'feature_code']);
        });
        Schema::create('subscription_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->string('event');
            $table->json('details');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_events');
        Schema::dropIfExists('institution_feature_overrides');
        Schema::table('subscription_invoices', fn (Blueprint $table) => $table->dropColumn(['semester_end', 'price_version', 'billing_policy']));
    }
};
