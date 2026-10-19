<?php

namespace App\Services\Tenancy;

use App\Models\Central\InstitutionDatabaseCopy;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Dedicated primaries never fall back to stale shared records on failure. */
class InstitutionDatabases
{
    public function primary(string $id): ?InstitutionDatabaseCopy
    {
        $db = DB::connection(config('tenancy.central_connection'));
        if (! $db->getSchemaBuilder()->hasColumn('institution_database_copies', 'direction')) {
            return null;
        }

        return InstitutionDatabaseCopy::whereKey($id)->where('direction', 'inbound')->first();
    }

    public function configuration(InstitutionDatabaseCopy $copy): array
    {
        app(InstitutionDatabaseCopies::class)->validateTarget($copy->database_name, $copy->username);

        return [...config('database.connections.'.config('tenancy.central_connection')), 'url' => null,
            'database' => $copy->database_name, 'username' => $copy->username, 'password' => $copy->password, 'tenant_scoped' => false];
    }

    public function connection(string $id)
    {
        $copy = $this->primary($id);
        if (! $copy) {
            return DB::connection(config('tenancy.central_connection'));
        }
        $name = 'school_'.str_replace('-', '', $id);
        try {
            $configuration = $this->configuration($copy);
            if (config('database.connections.'.$name) !== $configuration) {
                DB::purge($name);
                config(['database.connections.'.$name => $configuration]);
            }
            if ($copy->schema_version !== $this->schemaVersion()) {
                throw new RuntimeException('Institution schema upgrade required.');
            }
            $db = DB::connection($name);
            $state = $db->table(InstitutionDatabaseCopies::STATE)->where('id', 1)->first();
            if (! $state || $state->institution_id !== $id || $state->source_tag !== 'dedicated-primary-v1') {
                throw new RuntimeException('Ownership mismatch.');
            }

            return $db;
        } catch (\Throwable) {
            throw new HttpException(503, 'Institution database is unavailable. Contact platform support.');
        }
    }

    public function schemaVersion(): string
    {
        $files = [...glob(database_path('migrations/*.php')), ...glob(database_path('tenant-migrations/*.php'))];
        sort($files);

        return hash('sha256', implode('', array_map(fn ($file) => basename($file).hash_file('sha256', $file), $files)));
    }

    public function operations(string $id)
    {
        $central = DB::connection(config('tenancy.central_connection'));
        if (! $this->primary($id)) {
            return $central->table('operation_health')->where('tenant_id', $id)->get();
        }

        return $central->table('institution_record_snapshots')->where('institution_id', $id)->where('table_name', 'operation_health')->limit(100)->get()
            ->map(function ($row) use ($id) {
                $data = unserialize(base64_decode($row->payload, true), ['allowed_classes' => false]);

                return is_array($data) && ($data['tenant_id'] ?? null) === $id ? (object) $data : null;
            })->filter();
    }

    public function statistics(string $id): array
    {
        if ($copy = $this->primary($id)) {
            return $copy->statistics ?? ['students' => null, 'staff' => null];
        }
        $query = DB::connection(config('tenancy.central_connection'))->table('users')->where('tenant_id', $id)->whereNull('deleted_at')->whereNull('merged_into_id');

        return ['students' => (clone $query)->where('usertype', 'student')->count(), 'staff' => (clone $query)->where('usertype', '!=', 'student')->count()];
    }
}
