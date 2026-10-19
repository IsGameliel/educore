<?php

namespace App\Services\Tenancy;

use App\Models\Central\Institution;
use App\Models\Central\InstitutionDatabaseCopy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DirectAdminDatabases
{
    public function create(Institution $school): InstitutionDatabaseCopy
    {
        $account = config('tenancy.directadmin.username');
        $url = rtrim((string) config('tenancy.directadmin.url'), '/');
        $key = config('tenancy.directadmin.key');
        if (! config('tenancy.directadmin.enabled') || ! preg_match('/\A[a-zA-Z0-9]{1,16}\z/D', (string) $account)
            || ! preg_match('#\Ahttps://[a-zA-Z0-9.-]+(?::[0-9]+)?\z#D', $url) || ! $key) {
            throw new RuntimeException('Configure the DirectAdmin HTTPS endpoint, account and dedicated login key.');
        }

        return app(InstitutionMutex::class)->run($school->id, function () use ($school, $account, $url, $key) {
            $copy = InstitutionDatabaseCopy::find($school->id);
            if ($copy && $copy->hosting_status === null) {
                throw new RuntimeException('An operator-managed connection already exists.');
            }
            if ($copy && $copy->hosting_status === 'created') {
                return $copy;
            }
            if (! $copy) {
                $suffix = 'e'.bin2hex(random_bytes(3));
                $copy = InstitutionDatabaseCopy::create(['institution_id' => $school->id, 'database_name' => $account.'_'.$suffix,
                    'username' => $account.'_'.$suffix, 'password' => bin2hex(random_bytes(24)), 'configuration_version' => (string) Str::uuid(),
                    'enabled' => false, 'status' => 'provisioning', 'hosting_status' => 'planned']);
            }
            $request = Http::asForm()->withBasicAuth($account, $key)->connectTimeout(10)->timeout(40)->withoutRedirecting();
            try {
                $response = $request->get($url.'/CMD_API_DATABASES');
                parse_str($response->body(), $listing);
                if (! $response->successful() || ! isset($listing['list']) || ! is_array($listing['list'])) {
                    throw new RuntimeException('Unable to list databases.');
                }
                $exists = in_array($copy->database_name, $listing['list'], true);
                if ($exists && $copy->hosting_status === 'planned') {
                    throw new RuntimeException('Unmanaged database name collision.');
                }
                if (! $exists) {
                    $copy->forceFill(['hosting_status' => 'requested'])->save();
                    $suffix = substr($copy->database_name, strlen($account) + 1);
                    $response = $request->post($url.'/CMD_API_DATABASES', ['action' => 'create', 'name' => $suffix, 'user' => $suffix,
                        'passwd' => $copy->password, 'passwd2' => $copy->password]);
                    parse_str($response->body(), $result);
                    if (! $response->successful() || (string) ($result['error'] ?? '') !== '0') {
                        throw new RuntimeException('Creation refused.');
                    }
                }
                $name = 'institution_hosting_probe';
                config(['database.connections.'.$name => app(InstitutionDatabases::class)->configuration($copy)]);
                DB::purge($name);
                $db = DB::connection($name);
                if ($db->getSchemaBuilder()->getTableListing($db->getDatabaseName(), schemaQualified: false)) {
                    throw new RuntimeException('New database must be empty.');
                }
                $copy->forceFill(['hosting_status' => 'created', 'enabled' => true, 'status' => 'configured', 'failure_code' => null])->save();
                PlatformAudit::record('institution_database.created', $school->id, ['database_name' => $copy->database_name]);

                return $copy->fresh();
            } catch (\Throwable) {
                $copy->forceFill(['status' => 'failed', 'failure_code' => 'hosting'])->save();
                throw new RuntimeException('Institution database creation failed. Inspect DirectAdmin permissions and hosting limits; no existing database was deleted.');
            } finally {
                DB::purge('institution_hosting_probe');
                config(['database.connections.institution_hosting_probe' => null]);
            }
        });
    }
}
