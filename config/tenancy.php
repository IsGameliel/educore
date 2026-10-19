<?php

return [
    'enabled' => env('TENANCY_ENABLED', true),
    'legacy_hosts' => array_filter(array_map('trim', explode(',', env('TENANCY_LEGACY_HOSTS', in_array(env('APP_ENV', 'local'), ['local', 'testing']) ? 'localhost,127.0.0.1' : '')))),
    'central_connection' => 'central',
    'migration_username' => env('TENANCY_MIGRATION_USERNAME'),
    'migration_password' => env('TENANCY_MIGRATION_PASSWORD'),
    'tenant_connection' => 'tenant',
    'architecture' => 'shared_database',
    'platform_url' => env('PLATFORM_URL', 'http://educore.test:8000'),
    'institution_domain' => env('INSTITUTION_BASE_DOMAIN', 'educore.test'),
    'paystack_secret_key' => env('PLATFORM_PAYSTACK_SECRET_KEY'),
    'central_domains' => array_filter(array_map('trim', explode(',', env('CENTRAL_DOMAINS', 'educore.test')))),
    'central_migrations' => database_path('central-migrations'),
    // Existing migrations stay in place to preserve the current institution and tests.
    'tenant_migrations' => [database_path('migrations'), database_path('tenant-migrations')],
    'storage_root' => storage_path('app/private/tenants'),
    'lock_directory' => storage_path('framework/tenancy-locks'),
    'maintenance_file' => storage_path('framework/tenancy-maintenance.json'),
    'exports' => ['directory' => storage_path('app/private/institution-exports'), 'retention_days' => 7, 'max_bytes' => 64 * 1024 * 1024, 'max_rows' => 100000, 'max_ready' => 5],
    'operations' => ['max_jobs' => 1000, 'task_timeout' => 120, 'retry_after' => 180],
    'database_copies' => ['max_rows' => 100000, 'max_bytes' => 64 * 1024 * 1024],
    'directadmin' => [
        'enabled' => env('DIRECTADMIN_DATABASES_ENABLED', false),
        'url' => env('DIRECTADMIN_URL'),
        'username' => env('DIRECTADMIN_USERNAME'),
        'key' => env('DIRECTADMIN_LOGIN_KEY'),
    ],
    'billing' => [
        'rules_status' => 'confirmed_semester_fixed_v1',
        'billable_student_basis' => 'enrolled',
        'student_count_census' => 'semester_start',
        'promotion_semesters' => 1,
        'renewal_rate' => 'standard',
    ],
];
