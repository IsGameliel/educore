<?php

namespace App\Services\Tenancy;

use App\Models\Central\Institution;
use App\Models\Central\InstitutionDomain;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class InstitutionProvisioner
{
    public function register(array $input): Institution
    {
        $input['domain'] = InstitutionDomains::normalize($input['domain'] ?? '');
        $input['owner_email'] = strtolower(trim($input['owner_email'] ?? ''));
        $input['slug'] = strtolower(trim($input['slug'] ?? ''));
        Validator::make($input, [
            'slug' => ['required', 'string', 'max:63', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D'],
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:253', 'regex:/\A(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\z/D'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255'],
            'plan_code' => ['required', 'string', 'exists:'.config('tenancy.central_connection').'.plans,code'],
            'timezone' => ['required', 'timezone'],
        ])->validate();
        if (in_array($input['domain'], config('tenancy.central_domains'), true)) {
            throw new RuntimeException('A central domain cannot belong to an institution.');
        }

        return DB::connection(config('tenancy.central_connection'))->transaction(function () use ($input) {
            if ($institution = Institution::where('slug', $input['slug'])->first()) {
                foreach (['name', 'owner_name', 'owner_email', 'plan_code', 'timezone'] as $field) {
                    if ($input[$field] !== $institution->$field) {
                        throw new RuntimeException('Existing institution details differ; use its original provisioning details.');
                    }
                }
                if (! $institution->domains()->where('domain', $input['domain'])->exists()) {
                    throw new RuntimeException('Institution domain does not match.');
                }

                return $institution;
            }
            if (InstitutionDomain::where('domain', $input['domain'])->exists()) {
                throw new RuntimeException('Domain is already assigned.');
            }
            $institution = Institution::create([
                ...collect($input)->only(['slug', 'name', 'owner_name', 'owner_email', 'plan_code', 'timezone'])->all(),
            ]);
            $institution->domains()->create(['domain' => $input['domain']]);

            return $institution;
        });
    }

    public function provision(Institution $institution, string $password): Institution
    {
        Validator::make(['password' => $password], ['password' => ['required', 'string', 'min:12', 'max:255']])->validate();
        $connection = DB::connection(config('tenancy.central_connection'));
        $lockName = 'educore.provision.'.$institution->id;
        $mysqlLock = $connection->getDriverName() === 'mysql';
        $fileLock = null;
        if ($mysqlLock) {
            if ((int) $connection->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lockName])->acquired !== 1) {
                throw new RuntimeException('Institution provisioning is already running.');
            }
        } else {
            File::ensureDirectoryExists(config('tenancy.lock_directory'));
            $fileLock = fopen(config('tenancy.lock_directory').'/'.$institution->id.'.lock', 'c');
            if (! $fileLock || ! flock($fileLock, LOCK_EX | LOCK_NB)) {
                if ($fileLock) {
                    fclose($fileLock);
                }
                throw new RuntimeException('Institution provisioning is already running.');
            }
        }
        $attempt = null;
        $stage = 'records';
        try {
            $institution->refresh();
            if ($institution->status === 'ready') {
                return $institution;
            }
            if (! in_array($institution->status, ['pending', 'failed', 'provisioning'], true)) {
                throw new RuntimeException('Institution lifecycle state does not permit provisioning.');
            }
            $attempt = $connection->table('provisioning_attempts')->insertGetId([
                'institution_id' => $institution->id, 'status' => 'running', 'stage' => $stage, 'started_at' => now(),
            ]);
            $institution->forceFill(['status' => 'provisioning', 'failure_code' => null])->save();
            if (! $connection->getSchemaBuilder()->hasColumn('users', 'tenant_id')) {
                throw new RuntimeException('Initialize the shared schema before provisioning institutions.');
            }
            app(TenantContext::class)->run($institution, function () use ($institution, $password) {
                DB::transaction(function () use ($institution, $password) {
                    $profile = DB::table('institution_profiles')->where('tenant_id', $institution->id)->first();
                    DB::table('institution_profiles')->updateOrInsert(['tenant_id' => $institution->id], [
                        'name' => $institution->name, 'timezone' => $institution->timezone,
                        'created_at' => $profile?->created_at ?? now(), 'updated_at' => now(),
                    ]);
                    foreach (['registration_settings' => ['registration_open' => true, 'require_fee_clearance' => true, 'require_late_registration_fee' => false],
                        'promotion_policies' => ['max_carryovers' => 0]] as $table => $defaults) {
                        if (! DB::table($table)->where('tenant_id', $institution->id)->exists()) {
                            DB::table($table)->insert(['tenant_id' => $institution->id, ...$defaults, 'created_at' => now(), 'updated_at' => now()]);
                        }
                    }
                    // Never seed another school's example faculties, students, or courses.
                    (function () use ($institution, $password) {
                        $admin = User::withoutGlobalScopes()->where('tenant_id', $institution->id)->where('email', $institution->owner_email)->first();
                        if ($admin) {
                            if ($admin->usertype !== 'admin' || $admin->deleted_at || $admin->merged_into_id) {
                                throw new RuntimeException('Initial administrator account is unavailable.');
                            }
                        } else {
                            $admin = User::create(['name' => $institution->owner_name, 'email' => $institution->owner_email,
                                'password' => Hash::make($password), 'usertype' => 'admin']);
                        }
                        $team = $admin->ownedTeams()->firstOrCreate(['personal_team' => true], ['name' => $admin->name."'s Team"]);
                        if (! $admin->current_team_id) {
                            $admin->forceFill(['current_team_id' => $team->id])->save();
                        }
                    })();
                });
            }, provisioning: true);
            $stage = 'storage';
            $connection->table('provisioning_attempts')->where('id', $attempt)->update(['stage' => $stage]);
            File::ensureDirectoryExists(config('tenancy.storage_root').'/'.$institution->id, 0700);
            // Ready means records provisioned, not paid, activated, or publicly accessible.
            $institution->forceFill(['status' => 'ready', 'provisioned_at' => now(), 'failure_code' => null])->save();
            $connection->table('provisioning_attempts')->where('id', $attempt)->update([
                'status' => 'succeeded', 'stage' => 'complete', 'finished_at' => now(),
            ]);

            return $institution->refresh();
        } catch (Throwable $exception) {
            if ($attempt) {
                $code = $stage.'_failed';
                $institution->forceFill(['status' => 'failed', 'failure_code' => $code])->save();
                $connection->table('provisioning_attempts')->where('id', $attempt)->update([
                    'status' => 'failed', 'stage' => $stage, 'failure_code' => $code, 'finished_at' => now(),
                ]);
            }
            // Preserve failed setup records for safe inspection/retry. No destructive cleanup.
            throw $exception;
        } finally {
            if ($mysqlLock) {
                $connection->select('SELECT RELEASE_LOCK(?)', [$lockName]);
            } elseif ($fileLock) {
                flock($fileLock, LOCK_UN);
                fclose($fileLock);
            }
        }
    }
}
