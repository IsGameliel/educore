<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_database_copies', function (Blueprint $table) {
            $table->foreignUuid('institution_id')->primary()->constrained('institutions')->restrictOnDelete();
            $table->string('database_name', 64)->unique();
            $table->string('username', 80)->unique();
            $table->uuid('configuration_version');
            $table->text('password');
            $table->boolean('enabled')->default(false);
            $table->string('status')->default('configured');
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('failure_code')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->json('manifest')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Database copy configuration requires supervised recovery; it is not dropped automatically.');
    }
};
