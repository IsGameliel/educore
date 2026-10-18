<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable();
        });
        Schema::create('institution_integrations', function (Blueprint $table) {
            $table->foreignUuid('institution_id')->primary()->constrained('institutions')->restrictOnDelete();
            $table->text('credentials')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('institution_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->string('event');
            $table->string('actor')->nullable();
            $table->json('details');
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('institution_exports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->string('status')->default('ready');
            $table->string('checksum', 64);
            $table->unsignedBigInteger('bytes');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
        Schema::create('institution_restore_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->uuid('export_id');
            $table->uuid('safety_export_id');
            $table->string('previous_status');
            $table->string('status')->default('prepared');
            $table->timestamp('created_at');
            $table->timestamp('finished_at')->nullable();
        });
        Schema::create('institution_task_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->string('task');
            $table->string('slot');
            $table->string('status');
            $table->unsignedInteger('attempts')->default(1);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('failure_type')->nullable();
            $table->unique(['institution_id', 'task', 'slot']);
        });
        Schema::create('platform_runtime', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->text('value');
            $table->timestamp('updated_at');
        });
        Schema::create('platform_schema_rollouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('status');
            $table->text('backup_path')->nullable();
            $table->string('schema_version', 64)->nullable();
            $table->string('failure_type')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Institution operations rollback requires a verified operator backup.');
    }
};
