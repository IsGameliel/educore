<?php

namespace App\Services\Tenancy;

use App\Models\Central\Institution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/** Operator-only copy import. Never changes the source connection or existing schools. */
class LegacyInstitutionImport
{
    private const REFERENCES = ['actor_id' => 'users', 'target_user_id' => 'users', 'approver_id' => 'users', 'tests_id' => 'tests', 'prerequisite_id' => 'courses'];

    private const HISTORICAL = ['activity_logs' => ['properties'], 'result_revisions' => ['before', 'after'], 'student_account_merges' => ['account_snapshots', 'record_changes']];

    private array $maps = [];

    private array $detached = [];

    private array $legacyDocumentLinks = [];

    private string $location = '';

    private function db()
    {
        return DB::connection(config('tenancy.central_connection'));
    }

    private function mapped(string $table, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        if (! isset($this->maps[$table][(string) $value])) {
            throw new RuntimeException('Unresolved legacy relationship at '.$this->location.': '.$table.' identifier '.(string) $value);
        }

        return $this->maps[$table][(string) $value];
    }

    private function keyed(string $table, string $json): string
    {
        $value = LegacyJson::array($json);
        $mapped = [];
        foreach ($value as $id => $item) {
            $mapped[$this->mapped($table, $id)] = $item;
        }

        return json_encode((object) $mapped, JSON_THROW_ON_ERROR);
    }

    public function prepare(string $sourceName): array
    {
        if (app(TenantState::class)->id()) {
            throw new RuntimeException('Legacy import requires an operator context.');
        }
        if (in_array($sourceName, [config('tenancy.central_connection'), config('tenancy.tenant_connection'), DB::getDefaultConnection()], true)) {
            throw new RuntimeException('Use a separately restored source snapshot connection.');
        }
        $source = DB::connection($sourceName);
        $target = $this->db();
        if ($source->getDatabaseName() === DB::connection()->getDatabaseName()) {
            throw new RuntimeException('Source must be a restored copy, never the live legacy database.');
        }
        if ($source->getDatabaseName() === $target->getDatabaseName()) {
            throw new RuntimeException('Source and target must be distinct.');
        }
        $tables = app(InstitutionArchives::class)->tables();
        unset($tables['institution_profiles']);
        $sourceTables = $source->getSchemaBuilder()->getTableListing($source->getDriverName() === 'sqlite' ? 'main' : $source->getDatabaseName(), schemaQualified: false);
        $ignored = ['migrations', 'sessions', 'password_reset_tokens', 'personal_access_tokens', 'jobs', 'failed_jobs', 'job_batches', 'cache', 'cache_locks', 'operation_health'];
        foreach (array_diff($sourceTables, array_keys($tables), $ignored) as $table) {
            if ($source->table($table)->exists()) {
                throw new RuntimeException('Unreviewed nonempty legacy table: '.$table);
            }
        }
        $data = [];
        $this->maps = [];
        $this->detached = [];
        $this->legacyDocumentLinks = [];
        $counts = [];
        foreach ($tables as $table => $columns) {
            if (! in_array($table, $sourceTables, true)) {
                throw new RuntimeException('Legacy schema is missing table '.$table.'. Upgrade the restored copy first.');
            }
            $sourceColumns = $source->getSchemaBuilder()->getColumnListing($table);
            if (in_array('tenant_id', $sourceColumns, true)) {
                throw new RuntimeException('Source must be a single legacy school, not another shared platform.');
            }
            if (array_diff($sourceColumns, $columns)) {
                throw new RuntimeException('Unreviewed legacy columns in '.$table);
            }
            $data[$table] = $source->table($table)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
            $counts[$table] = count($data[$table]);
            $next = (int) $target->table($table)->max('id');
            foreach ($data[$table] as $row) {
                $this->maps[$table][(string) $row['id']] = ++$next;
            }
        }
        foreach ($data as $table => &$rows) {
            $links = [];
            foreach (app(TenantSchema::class)->foreignKeys($table) as $key) {
                if ($key['columns'] === ['tenant_id']) {
                    continue;
                }
                if (count($key['columns']) !== 1 || $key['foreign_columns'] !== ['id'] || ! isset($data[$key['foreign_table']])) {
                    throw new RuntimeException('Unsupported legacy relationship in '.$table);
                }
                $links[$key['columns'][0]] = $key['foreign_table'];
            }
            foreach (self::REFERENCES as $column => $foreign) {
                if (in_array($column, $tables[$table], true)) {
                    $links[$column] = $foreign;
                }
            }
            foreach ($tables[$table] as $column) {
                if ((str_ends_with($column, '_id') || str_ends_with($column, '_by')) && ! isset($links[$column]) && ! in_array($column, ['tenant_id', 'gateway_id', 'dispute_id', 'payable_id', 'subject_id'], true)) {
                    throw new RuntimeException('Unreviewed identifier '.$table.'.'.$column);
                }
            }
            foreach ($rows as &$row) {
                $this->location = $table.'.'.$row['id'];
                $row['id'] = $this->mapped($table, $row['id']);
                foreach ($links as $column => $foreign) {
                    if (array_key_exists($column, $row)) {
                        $row[$column] = $this->mapped($foreign, $row[$column]);
                    }
                }
                foreach ([['payable_type', 'payable_id'], ['subject_type', 'subject_id']] as [$type,$column]) {
                    if (isset($row[$type],$row[$column])) {
                        $class = Relation::getMorphedModel($row[$type]) ?: $row[$type];
                        if (! str_starts_with($class, 'App\\Models\\') || ! is_subclass_of($class, Model::class)) {
                            throw new RuntimeException('Unreviewed legacy polymorphic type in '.$table);
                        }
                        $foreign = (new $class)->getTable();
                        if ($table === 'activity_logs' && $column === 'subject_id' && ! isset($this->maps[$foreign][(string) $row[$column]])) {
                            $this->detached[] = ['record' => $this->location, 'column' => $column, 'legacy_type' => $row[$type], 'legacy_id' => $row[$column], 'reason' => 'historical subject no longer exists'];
                            $properties = LegacyJson::array($row['properties'] ?? '{}');
                            if (isset($properties['_legacy_subject'])) {
                                throw new RuntimeException('Reserved legacy audit metadata already exists.');
                            }
                            $properties['_legacy_subject'] = ['type' => $row[$type], 'id' => $row[$column]];
                            $row['properties'] = json_encode($properties, JSON_THROW_ON_ERROR);
                            $row[$column] = null;
                            $row[$type] = null;
                        } else {
                            $row[$column] = $this->mapped($foreign, $row[$column]);
                        }
                    }
                }
                if ($table === 'results') {
                    foreach (['transcript_path', 'full_transcript_path'] as $column) {
                        if (! empty($row[$column])) {
                            $filename = basename(parse_url($row[$column], PHP_URL_PATH) ?: $row[$column]);
                            $document = collect($data['transcript_documents'])->first(fn ($doc) => basename($doc['path']) === $filename && ($this->maps['users'][(string) $doc['user_id']] ?? null) === $row['user_id']);
                            if ($document) {
                                $row[$column] = '/documents/transcripts/'.rawurlencode($filename);
                            } else {
                                $this->legacyDocumentLinks[] = ['record' => $this->location, 'column' => $column, 'legacy_url' => $row[$column], 'reason' => 'No verified transcript document metadata; retain as source evidence and reissue through review'];
                                $row[$column] = null;
                            }
                        }
                    }
                }
                if ($table === 'users') {
                    $row['remember_token'] = null;
                    foreach (['email_otp_hash', 'email_otp_address', 'email_otp_expires_at', 'email_otp_sent_at'] as $column) {
                        if (array_key_exists($column, $row)) {
                            $row[$column] = null;
                        }
                    }
                    if (isset($row['email_otp_attempts'])) {
                        $row['email_otp_attempts'] = 0;
                    }
                }
                if ($table === 'attendance_sessions') {
                    foreach (['scan_token', 'scan_expires_at'] as $column) {
                        $row[$column] = null;
                    }
                }
                if (in_array($table, ['responses', 'test_attempts'], true)) {
                    $row['answers'] = $this->keyed('questions', $row['answers']);
                }
                if ($table === 'test_attempts') {
                    $questions = LegacyJson::array($row['questions']);
                    foreach ($questions as &$question) {
                        $question['id'] = $this->mapped('questions', $question['id']);
                    }unset($question);
                    $row['questions'] = json_encode($questions, JSON_THROW_ON_ERROR);
                }
                if ($table === 'transcript_documents') {
                    $row['result_versions'] = $this->keyed('results', $row['result_versions']);
                }
            }unset($row);
        }unset($rows);

        return ['rows' => $data, 'counts' => $counts, 'maps' => $this->maps, 'historical_json' => self::HISTORICAL, 'source_connection' => $sourceName, 'source_database' => $source->getDatabaseName(), 'detached_historical_references' => $this->detached, 'unverified_legacy_document_links' => $this->legacyDocumentLinks];
    }

    public function import(Institution $school, array $prepared, string $filesRoot, bool $rehearsalAllowMissing = false): array
    {
        return app(InstitutionMutex::class)->run($school->id, function () use ($school, $prepared, $filesRoot, $rehearsalAllowMissing) {
            $school = $school->fresh();
            if ($school->status !== 'pending') {
                throw new RuntimeException('Import requires a new pending institution.');
            }
            $db = $this->db();
            $documents = app(LegacyDocumentAudit::class)->inspect($prepared['source_connection'], $filesRoot);
            $disposable = (bool) preg_match('/\Aeducore_migration_[0-9]{8}_[a-f0-9]{12}_shared\z/D', $db->getDatabaseName());
            if (! $documents['ready'] && ! ($rehearsalAllowMissing && $disposable)) {
                throw new RuntimeException('Source document references are missing. Recover the originals before production import.');
            }
            foreach (array_keys($prepared['rows']) as $table) {
                if ($db->table($table)->where('tenant_id', $school->id)->exists()) {
                    throw new RuntimeException('Institution is not empty.');
                }
            }
            $root = rtrim(config('tenancy.storage_root'), '/\\').'/'.$school->id;
            if (file_exists($root)) {
                throw new RuntimeException('Destination files already exist.');
            }
            $admin = collect($prepared['rows']['users'])->first(fn ($r) => $r['usertype'] === 'admin' && $r['email'] === $school->owner_email && ! $r['deleted_at'] && ! $r['merged_into_id']);
            if (! $admin) {
                throw new RuntimeException('Owner must identify an active source administrator.');
            }
            $files = [];
            foreach (['private', 'public'] as $disk) {
                $base = realpath($filesRoot.'/'.$disk);
                if (! $base || is_link($filesRoot.'/'.$disk)) {
                    throw new RuntimeException('Verified snapshot file root missing.');
                }
                foreach (File::allFiles($base) as $file) {
                    $path = $file->getRealPath();
                    if ($file->isLink() || ! $path || ! str_starts_with($path, $base.DIRECTORY_SEPARATOR)) {
                        throw new RuntimeException('Unsafe source file.');
                    }$relative = $disk.'/'.str_replace('\\', '/', substr($path, strlen($base) + 1));
                    $files[$relative] = ['path' => $path, 'sha256' => hash_file('sha256', $path)];
                }
            }
            // Leave a failed/aborted import blocked; no automatic destructive cleanup.
            $school->forceFill(['status' => 'migrating', 'subscription_status' => 'awaiting_payment'])->save();
            try {
                File::ensureDirectoryExists($root, 0700);
                foreach ($files as $relative => $file) {
                    $to = $root.'/'.$relative;
                    File::ensureDirectoryExists(dirname($to), 0700);
                    if (! copy($file['path'], $to) || ! hash_equals($file['sha256'], hash_file('sha256', $to))) {
                        throw new RuntimeException('Imported file verification failed.');
                    }chmod($to, 0600);
                }
                $mysql = $db->getDriverName() === 'mysql';
                if ($mysql) {
                    $db->statement('SET FOREIGN_KEY_CHECKS=0');
                }
                try {
                    $db->transaction(function () use ($school, $db, $prepared, $mysql) {
                        if (! $mysql) {
                            $db->statement('PRAGMA defer_foreign_keys=ON');
                        }
                        foreach ($prepared['rows'] as $table => $rows) {
                            foreach (array_chunk($rows, 100) as $chunk) {
                                if ($chunk) {
                                    $db->table($table)->insert(array_map(fn ($r) => [...$r, 'tenant_id' => $school->id], $chunk));
                                }
                            }
                        }
                        $db->table('institution_profiles')->insert(['tenant_id' => $school->id, 'name' => $school->name, 'timezone' => $school->timezone, 'created_at' => now(), 'updated_at' => now()]);
                        foreach ($prepared['counts'] as $table => $count) {
                            if ($db->table($table)->where('tenant_id', $school->id)->count() !== $count) {
                                throw new RuntimeException('Imported count mismatch: '.$table);
                            }
                        }
                        $school->forceFill(['status' => 'imported', 'provisioned_at' => now()])->save();
                        InstitutionAudit::record($school->id, 'legacy_snapshot_imported', 'operator', ['source_database' => $prepared['source_database'], 'counts' => $prepared['counts'], 'historical_json_uses_source_ids' => true]);
                    });
                } finally {
                    if ($mysql) {
                        $db->statement('SET FOREIGN_KEY_CHECKS=1');
                    }
                }
                $report = ['institution_id' => $school->id, 'source_database' => $prepared['source_database'], 'counts' => $prepared['counts'], 'maps' => $prepared['maps'], 'files' => array_map(fn ($f) => $f['sha256'], $files), 'historical_json' => $prepared['historical_json'], 'detached_historical_references' => $prepared['detached_historical_references'], 'unverified_legacy_document_links' => $prepared['unverified_legacy_document_links'], 'status' => 'imported_awaiting_review'];
                File::put($root.'/migration-report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
                chmod($root.'/migration-report.json', 0600);

                return $report;
            } catch (\Throwable $e) {
                $school->forceFill(['status' => 'migration_failed', 'failure_code' => 'legacy_import_failed'])->save();
                if ($e instanceof QueryException) {
                    throw new RuntimeException('Database constraint rejected import (SQLSTATE '.$e->getCode().'). Inspect the private preflight report.');
                }throw $e;
            }
        });
    }
}
