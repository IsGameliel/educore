<?php

namespace App\Console\Commands;

use App\Services\Tenancy\InstitutionDatabaseCopies;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CheckDirectAdminDatabases extends Command
{
    protected $signature = 'tenancy:hosting-check';

    protected $description = 'Read-only DirectAdmin authentication and MySQL compatibility preflight';

    public function handle(): int
    {
        $url = rtrim((string) config('tenancy.directadmin.url'), '/');
        $account = config('tenancy.directadmin.username');
        $key = config('tenancy.directadmin.key');
        if (! preg_match('#\Ahttps://[a-zA-Z0-9.-]+(?::[0-9]+)?\z#D', $url) || ! $account || ! $key) {
            $this->error('Configure DIRECTADMIN_URL, DIRECTADMIN_USERNAME and DIRECTADMIN_LOGIN_KEY privately on the server.');

            return self::FAILURE;
        }
        try {
            $version = DB::connection(config('tenancy.central_connection'))->selectOne('SELECT VERSION() AS version')->version;
            if (! InstitutionDatabaseCopies::supportsAtomicRename($version)) {
                throw new \RuntimeException('Unsupported database server');
            }
            $response = Http::withBasicAuth($account, $key)->connectTimeout(10)->timeout(30)->withoutRedirecting()->get($url.'/CMD_API_DATABASES');
            parse_str($response->body(), $data);
            if (! $response->successful() || ! isset($data['list']) || ! is_array($data['list'])) {
                throw new \RuntimeException('API authentication failed');
            }
            $this->info('DirectAdmin database listing authenticated; MySQL compatibility verified. No databases were created or changed.');

            return self::SUCCESS;
        } catch (\Throwable) {
            $this->error('Hosting preflight failed. Check HTTPS, login-key permissions, allowed server IP and database server version.');

            return self::FAILURE;
        }
    }
}
