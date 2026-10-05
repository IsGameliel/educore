<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class StudentAccountMerge
{
    public const PROFILE_FIELDS = ['matric_number', 'department_id', 'level', 'entry_year', 'program', 'profile_photo_path'];

    private const DEDUPLICATE = ['course_user', 'team_user', 'student_credit_limits', 'attendance_records', 'course_registrations'];

    private ?array $schemaTables = null;
    private array $foreignKeys = [];
    private array $indexes = [];
    private array $referenceChecks = [];

    public function preview(array $ids, int $retainedId, bool $lock = false): array
    {
        // Keep metadata local to this preview, including transaction retries.
        $this->schemaTables = null;
        $this->foreignKeys = $this->indexes = $this->referenceChecks = [];
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);
        $query = User::withoutGlobalScopes()->whereIn('id', $ids)->orderBy('id');
        $accounts = ($lock ? $query->lockForUpdate() : $query)->get();
        if (count($ids) < 2 || count($ids) > 10 || !in_array($retainedId, $ids, true)
            || $accounts->count() !== count($ids)
            || $accounts->contains(fn ($user) => $user->usertype !== 'student' || $user->merged_into_id)) {
            throw ValidationException::withMessages(['accounts' => 'Select between two and ten active student accounts, including the account to keep.']);
        }

        $records = [];
        $conflicts = [];
        $duplicates = [];
        foreach ($this->ownershipColumns() as $table => $column) {
            $query = DB::table($table)->whereIn($column, $ids);
            $rows = ($lock ? $query->lockForUpdate() : $query)->get()->map(fn ($row) => (array) $row)->all();
            usort($rows, fn ($a, $b) => strcmp(json_encode($a), json_encode($b)));
            $records[$table] = ['column' => $column, 'rows' => $rows];
            $keys = [];
            foreach ($this->tableIndexes($table) as $index) {
                if ($index['unique'] && in_array($column, $index['columns'], true)) {
                    $keys[] = ['columns' => array_values(array_diff($index['columns'], [$column])), 'nullable' => true];
                }
            }
            // These business rules also apply where the database has no unique constraint.
            if ($table === 'course_registrations') {
                $keys[] = ['columns' => ['course_id', 'session', 'semester'], 'nullable' => false];
            }
            if ($table === 'responses') {
                $keys[] = ['columns' => ['test_id'], 'nullable' => false];
            }
            if ($table === 'results') {
                $keys[] = ['columns' => ['department_id', 'course_code', 'session', 'semester', '_attempt_group'], 'nullable' => false];
            }
            $annualSessions = [];
            if ($table === 'tuition_invoices') {
                $annualSessions = array_column(array_filter($rows, fn ($row) => $row['active_slot'] !== null && $row['period'] === 'Annual'), 'academic_session_id');
                $keys[] = ['columns' => ['academic_session_id', '_billing_period'], 'nullable' => false];
            }
            $seen = [];
            foreach ($keys as $key) {
                $groups = [];
                foreach ($rows as $row) {
                    if ($table === 'results' && !empty($row['deleted_at'])) {
                        continue;
                    }
                    if ($table === 'tuition_invoices' && in_array('_billing_period', $key['columns'], true) && $row['active_slot'] === null) {
                        continue;
                    }
                    $values = array_map(function ($field) use ($row, $annualSessions) {
                        $value = match ($field) {
                            '_attempt_group' => ($row['attempt_type'] ?? 'regular') === 'resit' ? 'resit' : 'regular/repeat',
                            '_billing_period' => in_array($row['academic_session_id'], $annualSessions) ? 'Annual overlap' : $row['period'],
                            default => $row[$field] ?? null,
                        };
                        // Match case-insensitive identifiers conservatively even on SQLite.
                        return is_string($value) ? mb_strtolower(trim($value)) : $value;
                    }, $key['columns']);
                    if ($key['nullable'] && in_array(null, $values, true)) {
                        continue;
                    }
                    $groups[json_encode($values)][] = $row;
                }
                foreach ($groups as $group) {
                    if (count(array_unique(array_column($group, $column))) < 2) {
                        continue;
                    }
                    $groupIds = array_column($group, 'id');
                    sort($groupIds);
                    $signature = json_encode($groupIds);
                    if (isset($seen[$signature])) {
                        continue;
                    }
                    $seen[$signature] = true;
                    $entry = ['table' => $table, 'rows' => $group, 'fields' => $key['columns']];
                    $comparable = array_map(function ($row) use ($column, $table) {
                        unset($row['id'], $row[$column], $row['created_at'], $row['updated_at']);
                        if ($table === 'course_registrations') {
                            // Separate registrations can have different provenance
                            // while representing the same enrollment. Originals are
                            // archived in full, including actor and registration date.
                            unset($row['acted_by'], $row['registration_date']);
                        }
                        return $row;
                    }, $group);
                    if (in_array($table, self::DEDUPLICATE, true) && count(array_unique(array_map('json_encode', $comparable))) === 1
                        && !$this->hasIncomingReferences($table, $table === 'course_registrations')
                        && ($table !== 'course_registrations' || !DB::table('results')
                            ->whereIn('course_registration_id', $groupIds)->whereNotIn('user_id', $ids)->exists())) {
                        $duplicates[] = $entry;
                    } else {
                        $conflicts[] = $entry;
                    }
                }
            }
        }

        $snapshots = $accounts->map(fn ($user) => $user->only(array_merge(['id', 'name', 'email', 'usertype', 'updated_at'], self::PROFILE_FIELDS)))->all();
        $digest = hash_hmac('sha256', json_encode([$retainedId, $snapshots, $records]), config('app.key'));
        return compact('accounts', 'records', 'conflicts', 'duplicates', 'snapshots', 'digest', 'retainedId');
    }

    public function merge(array $ids, int $retainedId, array $profileSources, string $reason, int $actorId, string $digest): void
    {
        DB::transaction(function () use ($ids, $retainedId, $profileSources, $reason, $actorId, $digest) {
            $actor = User::whereKey($actorId)->first();
            abort_unless($actor?->dashboardRole() === 'admin', 403);
            $preview = $this->preview($ids, $retainedId, true);
            if (!hash_equals($preview['digest'], $digest)) {
                throw ValidationException::withMessages(['merge' => 'These accounts or records changed. Open a fresh preview before merging.']);
            }
            if ($preview['conflicts']) {
                throw ValidationException::withMessages(['merge' => 'Resolve the conflicting records shown in the preview before merging. No accounts were changed.']);
            }
            $accounts = $preview['accounts']->keyBy('id');
            $retained = $accounts[$retainedId];
            $sourceIds = $accounts->keys()->reject(fn ($id) => $id === $retainedId)->values()->all();
            $profile = [];
            foreach (self::PROFILE_FIELDS as $field) {
                $source = (int) ($profileSources[$field] ?? $retainedId);
                if (!isset($accounts[$source])) {
                    throw ValidationException::withMessages(['profile' => 'Profile values must come from one of the selected accounts.']);
                }
                $profile[$field] = $accounts[$source]->getAttribute($field);
            }
            // Free a matric number held by an archived account before assigning it to the retained account.
            if ($profile['matric_number'] && $profile['matric_number'] !== $retained->matric_number) {
                DB::table('users')->whereIn('id', $sourceIds)->where('matric_number', $profile['matric_number'])->update(['matric_number' => null]);
            }
            $changes = [];
            foreach ($preview['duplicates'] as $duplicate) {
                $table = $duplicate['table'];
                $column = $preview['records'][$table]['column'];
                $rows = collect($duplicate['rows'])->sortBy(fn ($row) => [(int) $row[$column] === $retainedId ? 0 : 1, $row['id']]);
                $winner = $rows->first();
                $removed = $rows->skip(1)->values()->all();
                if ($table === 'course_registrations') {
                    // Preserve published and soft-deleted results before removing
                    // an identical registration. Never recalculate scores here.
                    $linkedResults = DB::table('results')->whereIn('course_registration_id', array_column($removed, 'id'))
                        ->lockForUpdate()->get(['id', 'user_id', 'course_registration_id']);
                    foreach ($linkedResults as $result) {
                        $changes['reference_updates'][] = [
                            'table' => 'results', 'record_id' => $result->id, 'column' => 'course_registration_id',
                            'from' => $result->course_registration_id, 'to' => $winner['id'],
                        ];
                    }
                    DB::table('results')->whereIn('course_registration_id', array_column($removed, 'id'))
                        ->update(['course_registration_id' => $winner['id']]);
                }
                DB::table($table)->whereIn('id', array_column($removed, 'id'))->delete();
                $changes['deduplicated'][] = ['table' => $table, 'retained_record_id' => $winner['id'], 'archived_records' => $removed];
            }
            foreach ($preview['records'] as $table => $record) {
                $moved = DB::table($table)->whereIn($record['column'], $sourceIds)->get()->map(fn ($row) => (array) $row)->all();
                if ($moved) {
                    DB::table($table)->whereIn($record['column'], $sourceIds)->update([$record['column'] => $retainedId]);
                    $changes['transferred'][$table] = $moved;
                }
            }
            // This changes account ownership, not enrollment. Do not generate new
            // invoices through the enrollment observer while reconciling accounts.
            DB::table('users')->where('id', $retainedId)->update($profile + ['updated_at' => now()]);
            DB::table('users')->whereIn('id', $sourceIds)->update([
                'merged_into_id' => $retainedId, 'merged_at' => now(), 'remember_token' => null, 'updated_at' => now(),
            ]);
            DB::table('sessions')->whereIn('user_id', $sourceIds)->delete();
            DB::table('personal_access_tokens')->where('tokenable_type', $retained->getMorphClass())->whereIn('tokenable_id', $sourceIds)->delete();
            DB::table('password_reset_tokens')->whereIn('email', $accounts->only($sourceIds)->pluck('email'))->delete();
            DB::table('student_account_merges')->insert([
                'retained_user_id' => $retainedId, 'performed_by' => $actorId, 'reason' => $reason,
                'account_snapshots' => json_encode($preview['snapshots']), 'record_changes' => json_encode($changes), 'created_at' => now(),
            ]);
            \App\Models\ActivityLog::create([
                'actor_id' => $actorId, 'target_user_id' => $retainedId, 'action' => 'student_accounts_merged',
                'description' => 'Student accounts merged into account #'.$retainedId.'.',
                'properties' => ['archived_account_ids' => $sourceIds, 'reason' => $reason, 'profile_sources' => $profileSources],
            ]);
        }, 3);
    }

    private function ownershipColumns(): array
    {
        $result = [];
        foreach ($this->tables() as $table) {
            $name = $table['name'];
            if (in_array($name, ['users', 'sessions', 'student_account_merges'], true)) {
                continue;
            }
            $columns = Schema::getColumnListing($name);
            foreach (['user_id', 'student_id', 'target_user_id'] as $column) {
                if (in_array($column, $columns, true)) {
                    $result[$name] = $column;
                    break;
                }
            }
        }
        ksort($result);
        return $result;
    }

    private function hasIncomingReferences(string $table, bool $allowResultRegistration = false): bool
    {
        $cacheKey = $table.':'.(int) $allowResultRegistration;
        if (array_key_exists($cacheKey, $this->referenceChecks)) {
            return $this->referenceChecks[$cacheKey];
        }
        foreach ($this->tables() as $other) {
            $keys = $this->foreignKeys[$other['name']] ??= Schema::getForeignKeys($other['name']);
            foreach ($keys as $key) {
                if ($key['foreign_table'] === $table) {
                    if ($allowResultRegistration && $other['name'] === 'results'
                        && $key['columns'] === ['course_registration_id'] && $key['foreign_columns'] === ['id']) {
                        foreach ($this->tableIndexes('results') as $index) {
                            if ($index['unique'] && in_array('course_registration_id', $index['columns'], true)) {
                                return $this->referenceChecks[$cacheKey] = true;
                            }
                        }
                        continue;
                    }
                    return $this->referenceChecks[$cacheKey] = true;
                }
            }
        }
        return $this->referenceChecks[$cacheKey] = false;
    }

    private function tables(): array
    {
        $connection = DB::connection();
        $schema = in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)
            ? $connection->getDatabaseName() : null;
        return $this->schemaTables ??= Schema::getTables($schema);
    }

    private function tableIndexes(string $table): array
    {
        return $this->indexes[$table] ??= Schema::getIndexes($table);
    }
}
