<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenancy_schema', function (Blueprint $table) {
            $table->string('architecture')->primary();
        });
        DB::table('tenancy_schema')->insert(['architecture' => 'shared_database']);
        Schema::create('platform_administrators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('plans', function (Blueprint $table) {
            $table->string('code', 32)->primary();
            $table->string('name');
            $table->char('currency', 3)->default('NGN');
            $table->string('billing_unit')->default('student_per_semester');
            $table->unsignedBigInteger('standard_rate_minor');
            $table->unsignedBigInteger('promotional_rate_minor');
            $table->unsignedInteger('promotion_semesters')->default(1);
            $table->boolean('signup_enabled')->default(false);
            $table->timestamps();
        });
        Schema::create('features', function (Blueprint $table) {
            $table->string('code', 64)->primary();
            $table->string('name');
            $table->string('implementation_status')->default('needs_review');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('plan_features', function (Blueprint $table) {
            $table->string('plan_code', 32);
            $table->string('feature_code', 64);
            $table->primary(['plan_code', 'feature_code']);
            $table->foreign('plan_code')->references('code')->on('plans')->cascadeOnDelete();
            $table->foreign('feature_code')->references('code')->on('features')->cascadeOnDelete();
        });
        Schema::create('institutions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 63)->unique();
            $table->string('name');
            $table->string('status')->default('pending')->index();
            $table->string('plan_code', 32);
            $table->foreign('plan_code')->references('code')->on('plans')->restrictOnDelete();
            $table->string('owner_name');
            $table->string('owner_email');
            $table->string('timezone')->default('Africa/Lagos');
            $table->json('settings')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->string('failure_code')->nullable();
            $table->timestamps();
        });
        Schema::create('institution_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('domain', 253)->unique();
            $table->boolean('is_primary')->default(true);
            // Domain ownership and HTTP access are not enabled by provisioning.
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
        Schema::create('provisioning_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('status');
            $table->string('stage');
            $table->string('failure_code')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['provisioning_attempts', 'institution_domains', 'institutions', 'plan_features', 'features', 'plans', 'platform_administrators', 'tenancy_schema'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
