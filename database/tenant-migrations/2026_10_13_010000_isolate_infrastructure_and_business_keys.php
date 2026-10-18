<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['sessions', 'personal_access_tokens', 'cache', 'cache_locks', 'jobs', 'failed_jobs', 'job_batches'] as $table) {
            if (! Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->foreignUuid('tenant_id')->nullable()->index()->constrained('institutions')->restrictOnDelete());
            }
        }
        // A separate platform session table cannot load school session payloads.
        if (! Schema::hasTable('platform_sessions')) {
            Schema::create('platform_sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
        Schema::create('institution_password_resets', function (Blueprint $table) {
            $table->foreignUuid('tenant_id')->constrained('institutions')->restrictOnDelete();
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            $table->primary(['tenant_id', 'email']);
        });
        Schema::create('institution_sessions', function (Blueprint $table) {
            $table->foreignUuid('tenant_id')->constrained('institutions')->restrictOnDelete();
            $table->string('id');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
            $table->primary(['tenant_id', 'id']);
        });
        // Keep existing operations history while making the heartbeat key institution-specific.
        Schema::create('institution_operation_health', function (Blueprint $table) {
            $table->foreignUuid('tenant_id')->constrained('institutions')->restrictOnDelete();
            $table->string('name');
            $table->string('status');
            foreach (['started_at', 'succeeded_at', 'failed_at'] as $column) {
                $table->timestamp($column)->nullable();
            }
            $table->string('message')->nullable();
            $table->timestamps();
            $table->primary(['tenant_id', 'name']);
        });
        foreach (DB::table('operation_health')->get() as $row) {
            DB::table('institution_operation_health')->insert((array) $row);
        }
        Schema::drop('operation_health');
        Schema::rename('institution_operation_health', 'operation_health');

        $connection = DB::connection();
        $schema = $connection->getDriverName() === 'sqlite' ? 'main' : $connection->getDatabaseName();
        foreach (Schema::getTableListing($schema, schemaQualified: false) as $table) {
            if (! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }
            foreach (Schema::getIndexes($table) as $index) {
                if (! $index['unique'] || $index['primary'] || in_array('tenant_id', $index['columns'], true)) {
                    continue;
                }
                // Retain a supporting index for any existing single-column foreign keys.
                $support = 'tenant_support_'.substr(hash('sha256', $table.$index['name']), 0, 16);
                $unique = 'tenant_unique_'.substr(hash('sha256', $table.$index['name']), 0, 16);
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($index['columns'], $support));
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique(['tenant_id', ...$index['columns']], $unique));
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique($index['name']));
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Shared isolation rollback requires supervised backup restoration.');
    }
};
