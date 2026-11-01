<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INFRASTRUCTURE = ['migrations', 'tenancy_schema', 'platform_administrators', 'plans', 'features',
        'plan_features', 'institutions', 'institution_domains', 'provisioning_attempts',
        'platform_account_tokens', 'platform_login_sessions', 'platform_audit_events', 'institution_portal_handoffs', 'institution_database_copies', 'institution_record_snapshots',
        'institution_accounts', 'subscription_invoices', 'subscription_payments', 'subscription_events', 'institution_feature_overrides',
        'institution_integrations', 'institution_events', 'institution_exports', 'institution_restore_runs', 'institution_task_runs', 'platform_runtime', 'platform_schema_rollouts',
        'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions', 'password_reset_tokens', 'personal_access_tokens'];

    public function up(): void
    {
        if (! Schema::hasTable('tenancy_schema') || DB::table('tenancy_schema')->value('architecture') !== 'shared_database') {
            throw new RuntimeException('Shared schema setup requires an explicitly initialized platform database.');
        }
        foreach (['registration_settings', 'promotion_policies'] as $table) {
            if (! Schema::hasColumn($table, 'tenant_id')) {
                $row = DB::table($table)->first();
                if (DB::table($table)->count() > 1 || ($row && ($row->id !== 1 || $row->updated_by !== null))) {
                    throw new RuntimeException('Existing policy records require supervised data migration.');
                }
                DB::table($table)->delete();
            }
        }
        $connection = DB::connection();
        $schema = $connection->getDriverName() === 'sqlite' ? 'main' : $connection->getDatabaseName();
        foreach (Schema::getTableListing($schema, schemaQualified: false) as $table) {
            if (in_array($table, self::INFRASTRUCTURE, true) || Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Existing school records require supervised data migration.');
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignUuid('tenant_id')->constrained('institutions')->restrictOnDelete();
            });
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
            $table->dropUnique('users_matric_number_unique');
            $table->unique(['tenant_id', 'email']);
            $table->unique(['tenant_id', 'matric_number']);
        });
        Schema::table('registration_settings', fn (Blueprint $table) => $table->unique('tenant_id'));
        Schema::table('promotion_policies', fn (Blueprint $table) => $table->unique('tenant_id'));
        Schema::create('institution_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->unique()->constrained('institutions')->restrictOnDelete();
            $table->string('name');
            $table->string('timezone');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Shared institution schema rollback requires a supervised backup restoration.');
    }
};
