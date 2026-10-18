<?php

namespace App\Console\Commands;

use App\Models\Central\Institution;
use App\Models\Central\InstitutionAccount;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ResetInstitutionAdminPassword extends Command
{
    protected $signature = 'tenancy:admin-password {institution} {--email= : Defaults to the institution owner email}
        {--password-env=TENANT_ADMIN_PASSWORD : Initial secret environment variable for automation}';

    protected $description = 'Reset one institution administrator password and revoke its sessions and API tokens';

    public function handle(TenantContext $context): int
    {
        $institution = Institution::where('slug', $this->argument('institution'))->first();
        if (! $institution || $institution->status !== 'ready') {
            $this->error('A ready institution is required.');

            return self::FAILURE;
        }
        $password = getenv($this->option('password-env')) ?: ($this->input->isInteractive() ? $this->secret('New administrator password (12+ characters)') : null);
        Validator::make(['password' => $password], ['password' => 'required|string|min:12|max:255'])->validate();
        $email = $this->option('email') ?: $institution->owner_email;
        $context->run($institution, function () use ($email, $password) {
            DB::transaction(function () use ($email, $password) {
                $user = User::where('email', $email)->where('usertype', 'admin')->firstOrFail();
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => null])->save();
                $user->tokens()->delete();
                DB::table(config('session.table'))->where('user_id', $user->id)->delete();
                $account = InstitutionAccount::where('institution_id', $user->tenant_id)->where('email', $email)->first();
                if ($account) {
                    $account->forceFill(['password' => Hash::make($password), 'remember_token' => null])->save();
                    // Central sessions may belong to either guard; revoke only this account's sessions.
                    $guardKey = Auth::guard('institution_account')->getName();
                    $central = DB::connection(config('tenancy.central_connection'));
                    foreach ($central->table('platform_sessions')->where('user_id', $account->id)->get(['id', 'payload']) as $session) {
                        $payload = @unserialize(base64_decode($session->payload), ['allowed_classes' => false]);
                        if (is_array($payload) && ($payload[$guardKey] ?? null) === $account->id) {
                            $central->table('platform_sessions')->where('id', $session->id)->delete();
                        }
                    }
                }
            });
        });
        $this->info('Administrator password changed for '.$institution->slug.'. Existing sessions and tokens revoked.');

        return self::SUCCESS;
    }
}
