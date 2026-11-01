<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_database_copies', function (Blueprint $table) {
            $table->string('direction')->default('outbound');
            $table->string('hosting_status')->nullable();
            $table->timestamp('primary_activated_at')->nullable();
            $table->string('schema_version', 64)->nullable();
            $table->json('statistics')->nullable();
        });
        Schema::create('institution_record_snapshots', function (Blueprint $table) {
            $table->foreignUuid('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->string('table_name', 64);
            $table->unsignedInteger('row_number');
            $table->longText('payload');
            $table->primary(['institution_id', 'table_name', 'row_number'], 'institution_snapshot_row');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Dedicated database rollback requires supervised recovery.');
    }
};
