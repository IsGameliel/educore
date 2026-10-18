<?php

use App\Models\Central\PlatformAdministrator;
use App\Services\Tenancy\PlatformSecurity;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->platformRoot = storage_path('framework/testing/dashboard-'.Str::uuid());
    File::ensureDirectoryExists($this->platformRoot);
    touch($this->platformRoot.'/shared.sqlite');
    $sqlite = [...config('database.connections.sqlite'), 'database' => $this->platformRoot.'/shared.sqlite', 'url' => null];
    config(['database.connections.central' => $sqlite, 'database.connections.tenant' => [...$sqlite, 'database' => '__tenant_context_required__'],
        'tenancy.storage_root' => $this->platformRoot.'/files', 'tenancy.lock_directory' => $this->platformRoot.'/locks',
        'tenancy.maintenance_file' => $this->platformRoot.'/maintenance.json', 'tenancy.central_domains' => ['educore.test'],
        'tenancy.legacy_hosts' => ['localhost', '127.0.0.1'], 'tenancy.enabled' => true, 'session.driver' => 'database']);
    DB::purge('central');
    expect(Artisan::call('tenancy:install', ['--no-interaction' => true]))->toBe(0);
    $this->owner = PlatformAdministrator::create(['name' => 'Owner', 'email' => 'owner@platform.test', 'password' => 'Platform-password-123']);
    $this->owner->forceFill(['role' => 'owner'])->save();
});

afterEach(function () {
    DB::purge('central');
    DB::purge('tenant');
    File::deleteDirectory($this->platformRoot);
});

function platformTestSession($test, PlatformAdministrator $admin, bool $confirmed = true): string
{
    $id = Str::random(40);
    $key = Str::random(64);
    DB::connection('central')->table('platform_login_sessions')->insert(['id' => $key, 'administrator_id' => $admin->id,
        'security_version' => $admin->security_version, 'last_seen_at' => now()]);
    DB::connection('central')->table('platform_sessions')->insert(['id' => $id, 'user_id' => $admin->id,
        'payload' => base64_encode(serialize([Auth::guard('platform')->getName() => $admin->id, '_token' => Str::random(40),
            'platform_login_key' => $key, 'platform_confirmed_at' => $confirmed ? now()->timestamp : 0])), 'last_activity' => now()->timestamp]);
    $test->withCookie('educore_platform_session', $id);

    return $key;
}

it('enforces the role matrix on navigation and direct URLs', function () {
    foreach (['owner', 'finance', 'support', 'read_only'] as $role) {
        $admin = PlatformAdministrator::create(['name' => $role, 'email' => $role.'@roles.test', 'password' => 'Platform-password-123']);
        $admin->forceFill(['role' => $role])->save();
        platformTestSession($this, $admin->fresh());
        $this->get('http://educore.test/dashboard')->assertOk()->assertSee('Overview')->assertSee('My Account');
        foreach (['/administrators' => ['owner'], '/audit-log' => ['owner'], '/billing' => ['owner', 'finance', 'read_only'],
            '/operations' => ['owner', 'support', 'read_only'], '/reports' => ['owner', 'finance', 'read_only'], '/plans/create' => ['owner']] as $path => $roles) {
            $this->get('http://educore.test/dashboard'.$path)->assertStatus(in_array($role, $roles, true) ? 200 : 403);
        }
        if ($role !== 'owner') {
            $this->withCredentials()->postJson('http://educore.test/dashboard/administrators', [])->assertForbidden();
            $this->withCredentials()->postJson('http://educore.test/dashboard/plans', [])->assertForbidden();
            $this->withCredentials()->postJson('http://educore.test/dashboard/institutions/missing/features', [])->assertForbidden();
        }
    }
    $this->get('http://localhost/dashboard')->assertNotFound();
});

it('requires recent confirmation and the correct current password', function () {
    platformTestSession($this, $this->owner->fresh(), false);
    $this->post('http://educore.test/dashboard/administrators', [])->assertRedirect('http://educore.test/dashboard/confirm-password');
    $this->withCredentials()->postJson('http://educore.test/dashboard/confirm-password', ['current_password' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('current_password');
    $this->post('http://educore.test/dashboard/confirm-password', ['current_password' => 'Platform-password-123'])->assertRedirect();
    platformTestSession($this, $this->owner->fresh());
    $this->withCredentials()->postJson('http://educore.test/dashboard/account/password', ['current_password' => 'wrong', 'password' => 'Changed-password-123', 'password_confirmation' => 'Changed-password-123'])->assertUnprocessable()->assertJsonValidationErrors('current_password');
    expect(Hash::check('Platform-password-123', $this->owner->fresh()->password))->toBeTrue();
});

it('protects the last owner and revokes changed administrators without duplicating no-op events', function () {
    platformTestSession($this, $this->owner->fresh());
    $url = 'http://educore.test/dashboard/administrators/'.$this->owner->id;
    $data = ['role' => 'finance', 'is_active' => 1, 'reason' => 'Testing owner demotion', 'current_password' => 'Platform-password-123'];
    $this->withCredentials()->patchJson($url, $data)->assertUnprocessable()->assertJsonValidationErrors('role');
    $this->withCredentials()->patchJson($url, [...$data, 'role' => 'owner', 'is_active' => 0])->assertUnprocessable()->assertJsonValidationErrors('role');
    expect($this->owner->fresh()->role)->toBe('owner')->and($this->owner->fresh()->is_active)->toBeTrue();
    $other = PlatformAdministrator::create(['name' => 'Other', 'email' => 'other@test.test', 'password' => 'Platform-password-123']);
    $this->patch('http://educore.test/dashboard/administrators/'.$other->id, [...$data, 'role' => 'support'])->assertRedirect();
    expect($other->fresh()->security_version)->toBe(2);
    $this->patch('http://educore.test/dashboard/administrators/'.$other->id, [...$data, 'role' => 'support'])->assertRedirect();
    expect(DB::connection('central')->table('platform_audit_events')->where('event', 'administrator.updated')->count())->toBe(1);
    $this->get('http://educore.test/dashboard/audit-log?event=administrator.updated')->assertOk()->assertSee('administrator.updated');
});

it('rejects revoked sessions and cannot revoke another administrators session', function () {
    $key = platformTestSession($this, $this->owner->fresh());
    $this->delete('http://educore.test/dashboard/account/sessions/'.str_repeat('x', 64), ['current_password' => 'Platform-password-123'])->assertNotFound();
    DB::connection('central')->table('platform_login_sessions')->where('id', $key)->delete();
    $this->get('http://educore.test/dashboard')->assertRedirect('http://educore.test/dashboard/login');
    $this->owner->forceFill(['is_active' => false])->save();
    platformTestSession($this, $this->owner->fresh());
    $this->get('http://educore.test/dashboard')->assertRedirect('http://educore.test/dashboard/login');
});

it('stores hashed expiring one-use recovery tokens and preserves two-factor protection on reset', function () {
    Mail::shouldReceive('raw')->once()->withArgs(function ($body, $callback) {
        preg_match('~/dashboard/reset-password/([A-Za-z0-9]+)~', $body, $match);
        $this->resetToken = $match[1];

        return true;
    });
    $this->owner->forceFill(['two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()])->save();
    $this->post('http://educore.test/dashboard/forgot-password', ['email' => $this->owner->email])->assertRedirect();
    $row = DB::connection('central')->table('platform_account_tokens')->first();
    expect($row->token)->toBe(hash('sha256', $this->resetToken));
    $payload = ['token' => $this->resetToken, 'email' => $this->owner->email, 'password' => 'New-password-123', 'password_confirmation' => 'New-password-123'];
    $this->withCredentials()->postJson('http://educore.test/dashboard/reset-password', [...$payload, 'token' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('email');
    $this->post('http://educore.test/dashboard/reset-password', $payload)->assertRedirect('http://educore.test/dashboard/login');
    expect(Hash::check($payload['password'], $this->owner->fresh()->password))->toBeTrue()
        ->and($this->owner->fresh()->two_factor_confirmed_at)->not->toBeNull();
    $this->withCredentials()->postJson('http://educore.test/dashboard/reset-password', $payload)->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('invites administrators with a restricted role and rejects repeated or invalid invitations', function () {
    Mail::shouldReceive('raw')->once();
    platformTestSession($this, $this->owner->fresh());
    $data = ['name' => 'Finance colleague', 'email' => 'finance@invite.test', 'role' => 'finance', 'current_password' => 'Platform-password-123'];
    $this->withCredentials()->postJson('http://educore.test/dashboard/administrators', [...$data, 'role' => 'superuser'])->assertUnprocessable()->assertJsonValidationErrors('role');
    $this->post('http://educore.test/dashboard/administrators', $data)->assertRedirect();
    $this->withCredentials()->postJson('http://educore.test/dashboard/administrators', $data)->assertUnprocessable()->assertJsonValidationErrors('email');
    expect(PlatformAdministrator::where('email', $data['email'])->firstOrFail()->role)->toBe('finance')
        ->and(DB::connection('central')->table('platform_account_tokens')->where('email', $data['email'])->value('purpose'))->toBe('invite');
    $details = DB::connection('central')->table('platform_audit_events')->pluck('details')->implode(' ');
    expect($details)->not->toContain('Platform-password-123')->not->toContain('token');
});

it('changes passwords and invalidates all previous sessions', function () {
    $old = platformTestSession($this, $this->owner->fresh());
    $this->post('http://educore.test/dashboard/account/password', ['current_password' => 'Platform-password-123',
        'password' => 'Changed-password-123', 'password_confirmation' => 'Changed-password-123'])->assertRedirect();
    expect($this->owner->fresh()->security_version)->toBe(2)
        ->and(DB::connection('central')->table('platform_login_sessions')->where('id', $old)->exists())->toBeFalse()
        ->and(Hash::check('Changed-password-123', $this->owner->fresh()->password))->toBeTrue();
});

it('requires two-factor challenge before creating a session and consumes recovery codes once', function () {
    $this->owner->forceFill(['two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => [Hash::make('single-use-recovery')]])->save();
    $response = $this->post('http://educore.test/dashboard/login', ['email' => $this->owner->email, 'password' => 'Platform-password-123'])->assertRedirect('http://educore.test/dashboard/two-factor-challenge');
    expect(DB::connection('central')->table('platform_login_sessions')->count())->toBe(0);
    $this->withCookie('educore_platform_session', $response->getCookie('educore_platform_session')->getValue());
    $this->get('http://educore.test/dashboard')->assertRedirect('http://educore.test/dashboard/login');
    $this->post('http://educore.test/dashboard/two-factor-challenge', ['code' => 'single-use-recovery'])->assertRedirect('http://educore.test/dashboard');
    expect(DB::connection('central')->table('platform_login_sessions')->count())->toBe(1)
        ->and(PlatformSecurity::verifyFactor($this->owner->fresh(), 'single-use-recovery'))->toBeFalse();
});

it('confirms authenticator enrollment before enabling it and encrypts the stored secret', function () {
    platformTestSession($this, $this->owner->fresh());
    $response = $this->post('http://educore.test/dashboard/account/two-factor/setup', ['current_password' => 'Platform-password-123'])->assertRedirect();
    expect($this->owner->fresh()->two_factor_confirmed_at)->toBeNull();
    $this->withCookie('educore_platform_session', $response->getCookie('educore_platform_session')->getValue());
    $this->mock(TwoFactorAuthenticationProvider::class, function ($mock) {
        $mock->shouldReceive('verify')->once()->andReturn(true);
    });
    $this->post('http://educore.test/dashboard/account/two-factor/enable', ['current_password' => 'Platform-password-123', 'code' => '123456'])->assertRedirect();
    expect($this->owner->fresh()->two_factor_confirmed_at)->not->toBeNull()
        ->and(count($this->owner->fresh()->two_factor_recovery_codes))->toBe(8)
        ->and(DB::connection('central')->table('platform_administrators')->where('id', $this->owner->id)->value('two_factor_secret'))->not->toBe($this->owner->fresh()->two_factor_secret);
});

it('rejects expired recovery links and challenges invalidated by an account change', function () {
    DB::connection('central')->table('platform_account_tokens')->insert(['email' => $this->owner->email,
        'token' => hash('sha256', 'expired-token'), 'purpose' => 'reset', 'expires_at' => now()->subMinute(), 'created_at' => now()->subHours(2)]);
    $this->withCredentials()->postJson('http://educore.test/dashboard/reset-password', ['email' => $this->owner->email,
        'token' => 'expired-token', 'password' => 'New-password-123', 'password_confirmation' => 'New-password-123'])->assertUnprocessable();
    expect(Hash::check('Platform-password-123', $this->owner->fresh()->password))->toBeTrue();
    $this->owner->forceFill(['two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => [Hash::make('unused-code')]])->save();
    $response = $this->post('http://educore.test/dashboard/login', ['email' => $this->owner->email, 'password' => 'Platform-password-123'])->assertRedirect();
    $this->withCookie('educore_platform_session', $response->getCookie('educore_platform_session')->getValue());
    PlatformSecurity::revoke($this->owner->fresh());
    $this->post('http://educore.test/dashboard/two-factor-challenge', ['code' => 'unused-code'])->assertForbidden();
    expect(DB::connection('central')->table('platform_login_sessions')->count())->toBe(0);
});

it('rate limits sign-in failures and does not expose passwords in audit records', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('http://educore.test/dashboard/login', ['email' => $this->owner->email, 'password' => 'wrong-password'])->assertRedirect();
    }
    $this->post('http://educore.test/dashboard/login', ['email' => $this->owner->email, 'password' => 'wrong-password'])->assertStatus(429);
    expect(DB::connection('central')->table('platform_audit_events')->where('event', 'security.login_failed')->count())->toBe(5)
        ->and(DB::connection('central')->table('platform_audit_events')->pluck('details')->implode(' '))->not->toContain('wrong-password');
});

it('accepts real authenticator codes once and requires a factor to disable protection', function () {
    $engine = new Google2FA;
    $secret = $engine->generateSecretKey();
    $this->owner->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => [Hash::make('disable-recovery')]])->save();
    $code = $engine->getCurrentOtp($secret);
    expect(PlatformSecurity::verifyFactor($this->owner->fresh(), $code))->toBeTrue()
        ->and(PlatformSecurity::verifyFactor($this->owner->fresh(), $code))->toBeFalse();
    platformTestSession($this, $this->owner->fresh());
    $this->withCredentials()->deleteJson('http://educore.test/dashboard/account/two-factor', [
        'current_password' => 'Platform-password-123', 'code' => 'wrong'])->assertUnprocessable();
    expect($this->owner->fresh()->two_factor_confirmed_at)->not->toBeNull();
    $this->delete('http://educore.test/dashboard/account/two-factor', ['current_password' => 'Platform-password-123', 'code' => 'disable-recovery'])->assertRedirect();
    expect($this->owner->fresh()->two_factor_confirmed_at)->toBeNull()
        ->and($this->owner->fresh()->two_factor_secret)->toBeNull()
        ->and($this->owner->fresh()->security_version)->toBe(2);
});
