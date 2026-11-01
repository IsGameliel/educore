<?php

namespace App\Services\Tenancy;

use App\Models\Central\Institution;
use App\Models\Central\InstitutionExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class InstitutionArchives
{
    private const EXCLUDED = ['sessions', 'institution_sessions', 'password_reset_tokens', 'institution_password_resets', 'personal_access_tokens', 'cache', 'cache_locks', 'jobs', 'failed_jobs', 'job_batches', 'operation_health'];

    private const FINANCIAL = ['payments', 'payment_gateway_changes', 'payment_settlements', 'financial_approvals', 'tuition_invoices', 'tuition_charges', 'tuition_adjustments', 'tuition_clearances'];

    private function db()
    {
        return DB::connection(config('tenancy.central_connection'));
    }

    private function assertOwner(Institution $school): void
    {
        $current = app(TenantState::class)->id();
        if ($current !== null && $current !== $school->id) {
            throw new RuntimeException('Export institution ownership mismatch.');
        }
    }

    public function tables(): array
    {
        $db = app(TenantState::class)->id() ? app(InstitutionDatabases::class)->connection(app(TenantState::class)->id()) : $this->db();
        $schema = $db->getDriverName() === 'sqlite' ? 'main' : $db->getDatabaseName();
        $tables = [];
        foreach ($db->getSchemaBuilder()->getTableListing($schema, schemaQualified: false) as $table) {
            if (preg_match('/\A[a-zA-Z0-9_]+\z/D', $table) && ! in_array($table, self::EXCLUDED, true) && app(TenantSchema::class)->owned($table)) {
                $tables[$table] = $db->getSchemaBuilder()->getColumnListing($table);
            }
        }
        ksort($tables);

        return $tables;
    }

    private function schemaVersion(array $tables): string
    {
        return hash('sha256', json_encode($tables, JSON_THROW_ON_ERROR));
    }

    private function rows(string $id, array $tables): array
    {
        $data = [];
        $count = 0;
        $bytes = 0;
        foreach ($tables as $table => $columns) {
            $query = app(InstitutionDatabases::class)->connection($id)->table($table)->where('tenant_id', $id);
            if ($count + (clone $query)->count() > config('tenancy.exports.max_rows')) {
                throw new RuntimeException('Institution export exceeds the configured row quota.');
            }
            $rows = $query->get()->map(fn ($r) => (array) $r)->all();
            $count += count($rows);
            $bytes += strlen(json_encode($rows, JSON_THROW_ON_ERROR));
            if ($count > config('tenancy.exports.max_rows') || $bytes > config('tenancy.exports.max_bytes')) {
                throw new RuntimeException('Institution export exceeds the configured size or row quota.');
            }
            $data[$table] = $rows;
        }

        return $data;
    }

    private function financialFingerprint(array $rows): string
    {
        $data = [];
        foreach (self::FINANCIAL as $table) {
            $items = [];
            foreach ($rows[$table] ?? [] as $row) {
                ksort($row);
                $items[] = json_encode($row, JSON_THROW_ON_ERROR);
            } sort($items);
            $data[$table] = $items;
        }

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function path(InstitutionExport $export): string
    {
        if (! Str::isUuid($export->id) || ! Str::isUuid($export->institution_id)) {
            throw new RuntimeException('Invalid export identity.');
        }

        return rtrim(config('tenancy.exports.directory'), '/\\').'/'.$export->institution_id.'/'.$export->id.'.zip';
    }

    public function export(Institution $school, ?int $actor = null): InstitutionExport
    {
        $this->assertOwner($school);

        return app(InstitutionMutex::class)->run($school->id, function () use ($school, $actor) {
            if (InstitutionExport::where('institution_id', $school->id)->where('status', 'ready')->where('expires_at', '>', now())->count() >= config('tenancy.exports.max_ready')) {
                throw new RuntimeException('Export quota reached. Delete an unused export or wait until it expires.');
            }
            $tables = $this->tables();
            $rows = $this->rows($school->id, $tables);
            $payload = ['version' => 1, 'institution_id' => $school->id, 'schema' => $this->schemaVersion($tables), 'created_at' => now()->toIso8601String(),
                'profile' => ['name' => $school->name, 'timezone' => $school->timezone, 'settings' => $school->settings ?? []], 'rows' => $rows, 'files' => []];
            $files = [];
            $size = strlen(json_encode($payload, JSON_THROW_ON_ERROR));
            $root = rtrim(config('tenancy.storage_root'), '/\\').'/'.$school->id;
            foreach (['private', 'public'] as $disk) {
                if (is_dir($root.'/'.$disk)) {
                    $realRoot = realpath($root.'/'.$disk);
                    if (is_link($root.'/'.$disk)) {
                        throw new RuntimeException('Linked institution storage cannot be exported.');
                    }
                    foreach (File::allFiles($realRoot) as $file) {
                        $path = $file->getRealPath();
                        if ($file->isLink() || ! $path || ! str_starts_with($path, $realRoot.DIRECTORY_SEPARATOR)) {
                            throw new RuntimeException('Unsafe institution export file.');
                        }
                        $relative = $disk.'/'.str_replace('\\', '/', substr($path, strlen($realRoot) + 1));
                        $size += $file->getSize();
                        if ($size > config('tenancy.exports.max_bytes')) {
                            throw new RuntimeException('Institution export exceeds the configured size quota.');
                        }
                        $payload['files'][$relative] = ['bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $path)];
                        $files[$relative] = $path;
                    }
                }
            }
            $json = json_encode($payload, JSON_THROW_ON_ERROR);
            if (strlen($json) + array_sum(array_column($payload['files'], 'bytes')) > config('tenancy.exports.max_bytes')) {
                throw new RuntimeException('Institution export exceeds the configured size quota.');
            }
            $export = new InstitutionExport(['id' => (string) Str::uuid(), 'institution_id' => $school->id, 'created_by' => $actor, 'expires_at' => now()->addDays(config('tenancy.exports.retention_days'))]);
            $path = $this->path($export);
            File::ensureDirectoryExists(dirname($path), 0700);
            $partial = $path.'.partial';
            $zip = new ZipArchive;
            try {
                if ($zip->open($partial, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
                    throw new RuntimeException('Unable to create institution export.');
                }
                if (! $zip->addFromString('data.json', $json)) {
                    throw new RuntimeException('Unable to write export data.');
                }
                foreach ($files as $relative => $file) {
                    // Reading through Laravel also supports long Windows paths that libzip cannot open.
                    if (! $zip->addFromString('files/'.$relative, File::get($file))) {
                        throw new RuntimeException('Unable to archive institution file.');
                    }
                }
                if (! $zip->close()) {
                    throw new RuntimeException('Unable to complete institution export.');
                }
                rename($partial, $path);
                chmod($path, 0600);
                $export->forceFill(['status' => 'ready', 'checksum' => hash_file('sha256', $path), 'bytes' => filesize($path)])->save();
                InstitutionAudit::record($school->id, 'export_created', $actor ? 'school:'.$actor : 'operator', ['export_id' => $export->id, 'bytes' => $export->bytes]);

                return $export;
            } catch (\Throwable $e) {
                if (is_file($partial)) {
                    File::delete($partial);
                } if (! $export->exists && is_file($path)) {
                    File::delete($path);
                } throw $e;
            }
        });
    }

    private function read(Institution $school, InstitutionExport $export, array $tables): array
    {
        if ($export->institution_id !== $school->id || $export->status !== 'ready' || $export->expires_at->isPast()) {
            throw new RuntimeException('This institution export is unavailable or expired.');
        }
        $path = $this->path($export);
        if (! is_file($path) || is_link($path) || ! hash_equals($export->checksum, hash_file('sha256', $path))) {
            throw new RuntimeException('Export checksum verification failed.');
        }
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            throw new RuntimeException('Invalid institution export archive.');
        }
        try {
            $size = 0;
            $names = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $size += $stat['size'];
                $name = $stat['name'];
                if ($size > config('tenancy.exports.max_bytes') || isset($names[$name]) || str_contains($name, '..') || str_contains($name, '\\') || str_starts_with($name, '/') || ($name !== 'data.json' && ! preg_match('#\Afiles/(private|public)/[^\x00]+\z#D', $name))) {
                    throw new RuntimeException('Unsafe archive entry or size.');
                }
                $names[$name] = true;
            }
            $raw = $zip->getFromName('data.json');
            if (! is_string($raw)) {
                throw new RuntimeException('Export data missing.');
            }
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (($data['version'] ?? null) !== 1 || ($data['institution_id'] ?? null) !== $school->id || ($data['schema'] ?? null) !== $this->schemaVersion($tables) || array_keys($data['rows'] ?? []) !== array_keys($tables)) {
                throw new RuntimeException('Institution export schema or ownership does not match.');
            }
            if (count($names) !== count($data['files'] ?? []) + 1) {
                throw new RuntimeException('Archive file manifest mismatch.');
            }
            foreach ($data['files'] ?? [] as $relative => $metadata) {
                $bytes = $zip->getFromName('files/'.$relative);
                if (! is_string($bytes) || strlen($bytes) !== $metadata['bytes'] || ! hash_equals($metadata['sha256'], hash('sha256', $bytes))) {
                    throw new RuntimeException('Archive file checksum mismatch.');
                }
            }

            return $data;
        } finally {
            $zip->close();
        }
    }

    private function validateRows(Institution $school, array $data, array $tables, int $actor): void
    {
        $ids = [];
        $total = 0;
        foreach ($data['rows'] as $table => $rows) {
            if (! is_array($rows)) {
                throw new RuntimeException('Invalid exported rows.');
            }
            foreach ($rows as $row) {
                $total++;
                if ($total > config('tenancy.exports.max_rows') || ($row['tenant_id'] ?? null) !== $school->id || array_diff(array_keys($row), $tables[$table])) {
                    throw new RuntimeException('Export row ownership or columns do not match.');
                }
                if (isset($row['id'])) {
                    if (isset($ids[$table][(string) $row['id']])) {
                        throw new RuntimeException('Duplicate export row identifier.');
                    }
                    $ids[$table][(string) $row['id']] = true;
                    if ($this->db()->table($table)->where('id', $row['id'])->where('tenant_id', '!=', $school->id)->exists()) {
                        throw new RuntimeException('An export record identifier is now owned by another institution.');
                    }
                }
            }
        }
        $admin = collect($data['rows']['users'] ?? [])->first(fn ($r) => (int) $r['id'] === $actor);
        if (! $admin || $admin['usertype'] !== 'admin' || ($admin['deleted_at'] ?? null) || ($admin['merged_into_id'] ?? null)) {
            throw new RuntimeException('The current administrator must exist as an active administrator in the restore point.');
        }
        foreach ($data['rows'] as $table => $rows) {
            foreach (app(TenantSchema::class)->foreignKeys($table) as $key) {
                if (count($key['columns']) !== 1 || ! isset($data['rows'][$key['foreign_table']])) {
                    continue;
                }
                $column = $key['columns'][0];
                $target = $key['foreign_columns'][0];
                $allowed = [];
                foreach ($data['rows'][$key['foreign_table']] as $row) {
                    $allowed[(string) ($row[$target] ?? '')] = true;
                }
                foreach ($rows as $row) {
                    if (isset($row[$column]) && ! isset($allowed[(string) $row[$column]])) {
                        throw new RuntimeException('An exported relationship is incomplete or outside this institution.');
                    }
                }
            }
        }
    }

    public function restore(Institution $school, InstitutionExport $export, int $actor): InstitutionExport
    {
        $this->assertOwner($school);
        if (app(InstitutionDatabases::class)->primary($school->id)) {
            throw new RuntimeException('Dedicated database restores require supervised operator recovery.');
        }

        return app(InstitutionMutex::class)->run($school->id, function () use ($school, $export, $actor) {
            $school = $school->fresh();
            $tables = $this->tables();
            $data = $this->read($school, $export, $tables);
            $this->validateRows($school, $data, $tables, $actor);
            $current = $this->rows($school->id, $tables);
            if ($this->financialFingerprint($current) !== $this->financialFingerprint($data['rows'])) {
                throw new RuntimeException('Financial records changed after this export. Use operator-assisted selective recovery; school restore cannot rewind payments.');
            }
            $security = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'email', 'email_verified_at', 'usertype', 'deleted_at', 'merged_into_id'];
            $users = collect($current['users'] ?? [])->keyBy('id');
            foreach ($data['rows']['users'] as &$row) {
                if ($old = $users->get($row['id'])) {
                    foreach ($security as $column) {
                        if (array_key_exists($column, $old)) {
                            $row[$column] = $old[$column];
                        }
                    }
                } else {
                    $row['password'] = Hash::make(Str::random(64));
                    foreach (['remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'email_verified_at'] as $column) {
                        if (array_key_exists($column, $row)) {
                            $row[$column] = null;
                        }
                    }
                }
                $row['remember_token'] = null;
            } unset($row);
            $safety = $this->export($school, $actor);
            $root = rtrim(config('tenancy.storage_root'), '/\\').'/'.$school->id;
            File::ensureDirectoryExists($root, 0700);
            $realRoot = realpath($root);
            $base = realpath(config('tenancy.storage_root'));
            if (is_link($root) || ! $realRoot || ! $base || ! str_starts_with($realRoot, $base.DIRECTORY_SEPARATOR) || basename($realRoot) !== $school->id) {
                throw new RuntimeException('Unsafe institution storage root.');
            }
            $runId = (string) Str::uuid();
            $previousStatus = $school->status;
            $stage = $realRoot.'/.restore-'.$runId;
            File::ensureDirectoryExists($stage, 0700);
            $had = [];
            foreach (['private', 'public'] as $disk) {
                if (file_exists($realRoot.'/'.$disk) && (! is_dir($realRoot.'/'.$disk) || is_link($realRoot.'/'.$disk))) {
                    throw new RuntimeException('Unsafe current institution file root.');
                }$had[$disk] = is_dir($realRoot.'/'.$disk);
            }
            File::put($stage.'/journal.json', json_encode(['had' => $had], JSON_THROW_ON_ERROR));
            $this->db()->table('institution_restore_runs')->insert(['id' => $runId, 'institution_id' => $school->id, 'export_id' => $export->id, 'safety_export_id' => $safety->id, 'previous_status' => $previousStatus, 'status' => 'prepared', 'created_at' => now()]);
            $school->forceFill(['status' => 'restoring'])->save();
            $zip = new ZipArchive;
            $zip->open($this->path($export));
            $swapped = [];
            $success = false;
            try {
                foreach (['private', 'public'] as $disk) {
                    File::ensureDirectoryExists($stage.'/new-'.$disk, 0700);
                }
                foreach ($data['files'] as $relative => $metadata) {
                    [$disk,$name] = explode('/', $relative, 2);
                    $target = $stage.'/new-'.$disk.'/'.$name;
                    File::ensureDirectoryExists(dirname($target), 0700);
                    File::put($target, $zip->getFromName('files/'.$relative));
                    chmod($target, 0600);
                }
                $zip->close();
                $db = app(TenantState::class)->id() ? app(InstitutionDatabases::class)->connection(app(TenantState::class)->id()) : $this->db();
                $mysql = $db->getDriverName() === 'mysql';
                if ($mysql) {
                    $db->statement('SET FOREIGN_KEY_CHECKS=0');
                }
                try {
                    $db->transaction(function () use ($db, $school, $data, $tables, $realRoot, $stage, &$swapped, $mysql, $actor, $export, $safety, $runId, $previousStatus) {
                        if (! $mysql) {
                            $db->statement('PRAGMA defer_foreign_keys=ON');
                        }
                        foreach (array_keys($tables) as $table) {
                            $db->table($table)->where('tenant_id', $school->id)->delete();
                        }
                        foreach ($data['rows'] as $table => $rows) {
                            foreach (array_chunk($rows, 100) as $chunk) {
                                if ($chunk) {
                                    $db->table($table)->insert($chunk);
                                }
                            }
                        }
                        foreach (['institution_sessions', 'sessions', 'institution_password_resets', 'personal_access_tokens', 'jobs', 'job_batches', 'cache', 'cache_locks'] as $table) {
                            if ($db->getSchemaBuilder()->hasColumn($table, 'tenant_id')) {
                                $db->table($table)->where('tenant_id', $school->id)->delete();
                            }
                        }
                        $profile = $data['profile'];
                        if (! in_array($profile['timezone'], timezone_identifiers_list(), true) || ! is_string($profile['name']) || ! is_array($profile['settings'])) {
                            throw new RuntimeException('Invalid exported institution profile.');
                        }
                        $school->forceFill(['name' => $profile['name'], 'timezone' => $profile['timezone'], 'settings' => $profile['settings'], 'status' => $previousStatus])->save();
                        foreach (['private', 'public'] as $disk) {
                            if (is_link($realRoot.'/'.$disk)) {
                                throw new RuntimeException('Linked live storage cannot be restored.');
                            }
                            $swapped[$disk] = ['old' => false, 'new' => false];
                            if (is_dir($realRoot.'/'.$disk)) {
                                if (! rename($realRoot.'/'.$disk, $stage.'/old-'.$disk)) {
                                    throw new RuntimeException('Unable to preserve current institution files.');
                                }$swapped[$disk]['old'] = true;
                            }
                            if (! rename($stage.'/new-'.$disk, $realRoot.'/'.$disk)) {
                                throw new RuntimeException('Unable to activate restored institution files.');
                            }$swapped[$disk]['new'] = true;
                        }
                        $db->table('institution_restore_runs')->where('id', $runId)->update(['status' => 'committed', 'finished_at' => now()]);
                        InstitutionAudit::record($school->id, 'export_restored', 'school:'.$actor, ['export_id' => $export->id, 'safety_export_id' => $safety->id]);
                    });
                    $success = true;
                } finally {
                    if ($mysql) {
                        $db->statement('SET FOREIGN_KEY_CHECKS=1');
                    }
                }

                return $safety;
            } catch (\Throwable $e) {
                // Inspect the committed journal before reverting files, including an uncertain commit response.
                if ($this->db()->table('institution_restore_runs')->where('id', $runId)->value('status') === 'committed') {
                    $success = true;

                    return $safety;
                }
                $this->rollbackFiles($realRoot, $stage, $had);
                $this->db()->transaction(function () use ($school, $previousStatus, $runId) {
                    Institution::whereKey($school->id)->update(['status' => $previousStatus]);
                    $this->db()->table('institution_restore_runs')->where('id', $runId)->update(['status' => 'rolled_back', 'finished_at' => now()]);
                });
                InstitutionAudit::record($school->id, 'restore_failed', 'school:'.$actor, ['export_id' => $export->id, 'failure_type' => $e::class]);
                throw $e;
            } finally {
                // Remove only this run's staging path after successful commit; preserve failed material for inspection.
                if ($success && is_dir($stage) && str_starts_with(realpath($stage) ?: '', $realRoot.DIRECTORY_SEPARATOR)) {
                    File::deleteDirectory($stage);
                }
            }
        });
    }

    private function rollbackFiles(string $root, string $stage, array $had): void
    {
        foreach (['private', 'public'] as $disk) {
            $live = $root.'/'.$disk;
            $old = $stage.'/old-'.$disk;
            $new = $stage.'/new-'.$disk;
            $failed = $stage.'/failed-'.$disk;
            foreach ([$live, $old, $new, $failed] as $path) {
                if (is_link($path)) {
                    throw new RuntimeException('Restore recovery rejected linked storage.');
                }
            }
            if (is_dir($old)) {
                if (file_exists($live) && ! rename($live, $failed)) {
                    throw new RuntimeException('Restore file recovery needs operator inspection.');
                }
                if (! rename($old, $live)) {
                    throw new RuntimeException('Restore file recovery needs operator inspection.');
                }
            } elseif ($had[$disk]) {
                if (! is_dir($live) || (! is_dir($new) && ! is_dir($failed))) {
                    throw new RuntimeException('Restore file recovery is incomplete.');
                }
            } elseif (! is_dir($new) && file_exists($live)) {
                if (! rename($live, $failed)) {
                    throw new RuntimeException('Restore file recovery needs operator inspection.');
                }
            }
        }
    }

    public function recover(Institution $school, string $reason): int
    {
        if (strlen($reason) < 10) {
            throw new RuntimeException('Explain the operator recovery.');
        }

        return app(InstitutionMutex::class)->run($school->id, function () use ($school, $reason) {
            $count = 0;
            foreach ($this->db()->table('institution_restore_runs')->where('institution_id', $school->id)->where('status', 'prepared')->get() as $run) {
                if (! Str::isUuid($run->id)) {
                    throw new RuntimeException('Invalid restore journal identity.');
                }
                $root = realpath(rtrim(config('tenancy.storage_root'), '/\\').'/'.$school->id);
                $base = realpath(config('tenancy.storage_root'));
                if (! $root || ! $base || ! str_starts_with($root, $base.DIRECTORY_SEPARATOR) || basename($root) !== $school->id) {
                    throw new RuntimeException('Invalid restore recovery root.');
                }
                $stage = $root.'/.restore-'.$run->id;
                if (is_link($stage) || ! is_file($stage.'/journal.json')) {
                    throw new RuntimeException('Restore file journal requires operator inspection.');
                }
                $journal = json_decode(File::get($stage.'/journal.json'), true, 512, JSON_THROW_ON_ERROR);
                if (! isset($journal['had']['private'],$journal['had']['public'])) {
                    throw new RuntimeException('Restore file journal incomplete.');
                }
                $this->rollbackFiles($root, $stage, $journal['had']);
                $this->db()->transaction(function () use ($school, $run, $reason) {
                    Institution::whereKey($school->id)->update(['status' => $run->previous_status]);
                    $this->db()->table('institution_restore_runs')->where('id', $run->id)->update(['status' => 'rolled_back', 'finished_at' => now()]);
                    InstitutionAudit::record($school->id, 'restore_recovered', 'operator', ['restore_id' => $run->id, 'reason' => $reason]);
                });
                $count++;
            }

            return $count;
        });
    }

    public function remove(InstitutionExport $export, ?int $actor = null): void
    {
        $school = Institution::findOrFail($export->institution_id);
        $this->assertOwner($school);
        app(InstitutionMutex::class)->run($school->id, function () use ($export, $actor) {
            $path = $this->path($export);
            if (is_link($path)) {
                throw new RuntimeException('Linked exports cannot be removed.');
            }
            if (is_file($path)) {
                File::delete($path);
            }
            $export->update(['status' => 'deleted']);
            InstitutionAudit::record($export->institution_id, 'export_removed', $actor ? 'school:'.$actor : 'retention', ['export_id' => $export->id]);
        });
    }
}
