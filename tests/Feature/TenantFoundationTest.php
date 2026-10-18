<?php

use App\Models\Central\Institution;
use App\Models\Central\InstitutionAccount;
use App\Models\Central\Plan;
use App\Models\Central\PlatformAdministrator;
use App\Models\Central\SubscriptionInvoice;
use App\Models\Central\SubscriptionPayment;
use App\Models\Faculty;
use App\Models\PromotionPolicy;
use App\Models\RegistrationSetting;
use App\Models\User;
use App\Services\Tenancy\InstitutionProvisioner;
use App\Services\Tenancy\PlanCatalogue;
use App\Services\Tenancy\SubscriptionPayments;
use App\Services\Tenancy\TenantContext;
use App\Services\Tenancy\TenantState;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-17 12:00:00 UTC'));
    $this->tenantTestRoot = storage_path('framework/testing/tenancy-'.Str::uuid());
    File::ensureDirectoryExists($this->tenantTestRoot);
    touch($this->tenantTestRoot.'/shared.sqlite');
    $sqlite = [...config('database.connections.sqlite'), 'database' => $this->tenantTestRoot.'/shared.sqlite', 'url' => null];
    config([
        'database.connections.central' => $sqlite,
        'database.connections.tenant' => [...$sqlite, 'database' => '__tenant_context_required__'],
        'tenancy.storage_root' => $this->tenantTestRoot.'/files',
        'tenancy.lock_directory' => $this->tenantTestRoot.'/locks',
        'tenancy.exports.directory' => $this->tenantTestRoot.'/exports',
        'tenancy.maintenance_file' => $this->tenantTestRoot.'/maintenance.json',
        'tenancy.central_domains' => ['educore.test'],
        'tenancy.enabled' => true,
        'tenancy.paystack_secret_key' => null,
        'tenancy.legacy_hosts' => ['localhost', '127.0.0.1'],
    ]);
    DB::purge('central');
    $installed = Artisan::call('tenancy:install', ['--no-interaction' => true]);
    if ($installed !== 0) {
        throw new RuntimeException(Artisan::output());
    }
    $this->institutionInput = fn (string $slug, string $plan = 'silver') => [
        'slug' => $slug, 'name' => ucfirst($slug).' Institution', 'domain' => $slug.'.educore.test',
        'owner_name' => 'School Administrator', 'owner_email' => 'admin@example.test',
        'plan_code' => $plan, 'timezone' => 'Africa/Lagos',
    ];
});

afterEach(function () {
    app(TenantState::class)->leave();
    foreach (['central', 'tenant'] as $connection) {
        DB::purge($connection);
    }
    File::deleteDirectory($this->tenantTestRoot);
});

function institutionSignupInput(string $slug = 'signup-school'): array
{
    return ['name' => 'Signup School', 'domain' => 'portal.'.$slug.'.edu.ng', 'slug' => $slug, 'owner_name' => 'Owner', 'owner_email' => 'owner@example.test',
        'password' => 'Strong-signup-password-1', 'password_confirmation' => 'Strong-signup-password-1',
        'student_count' => 20, 'semester_name' => '2026/27 First Semester', 'semester_start' => '2026-10-01', 'semester_end' => '2027-01-31', 'confirm_census' => '1'];
}

function centralAccountSession($test, string $guard, int $id): string
{
    config(['session.driver' => 'database']);
    $sessionId = Str::random(40);
    $payload = [Auth::guard($guard)->getName() => $id, '_token' => Str::random(40)];
    if ($guard === 'platform') {
        PlatformAdministrator::whereKey($id)->update(['role' => 'owner']);
        $key = Str::random(64);
        DB::connection('central')->table('platform_login_sessions')->insert(['id' => $key, 'administrator_id' => $id, 'security_version' => 1, 'last_seen_at' => now()]);
        $payload += ['platform_login_key' => $key, 'platform_confirmed_at' => now()->timestamp];
    }
    DB::connection('central')->table('platform_sessions')->insert(['id' => $sessionId, 'user_id' => $id,
        'payload' => base64_encode(serialize($payload)), 'last_activity' => now()->timestamp]);
    $test->withCookie('educore_platform_session', $sessionId);

    return $sessionId;
}

it('serves Euvion centrally and hands the owner into a separately owned school hostname', function () {
    config(['tenancy.central_domains' => ['euvion.com.ng'], 'tenancy.platform_url' => 'https://euvion.com.ng',
        'tenancy.institution_domain' => 'euvion.com.ng', 'app.url' => 'https://euvion.com.ng']);
    $provisioner = app(InstitutionProvisioner::class);
    $school = $provisioner->provision($provisioner->register([
        ...($this->institutionInput)('custom-domain'), 'domain' => 'custom-domain.euvion.com.ng',
    ]), 'Secure-test-password-1');
    app(\App\Services\Tenancy\InstitutionLifecycle::class)->domain($school, 'academics.mudiameuniversity.edu.ng', 'School authorized DNS and HTTPS verified', 1);
    expect($school->domains()->first()->verified_at)->not->toBeNull();
    $this->get('https://euvion.com.ng/')->assertOk();
    $this->get('https://euvion.com.ng/dashboard')->assertRedirect('https://euvion.com.ng/dashboard/login');
    $this->get('https://euvion.com.ng/platform')->assertRedirect('https://euvion.com.ng/dashboard');
    $this->get('https://academics.mudiameuniversity.edu.ng/')->assertRedirect('https://academics.mudiameuniversity.edu.ng/login');
    $this->get('https://academics.mudiameuniversity.edu.ng/dashboard')->assertForbidden();
    $this->get('https://custom-domain.euvion.com.ng/login')->assertNotFound();
    $account = InstitutionAccount::create(['institution_id' => $school->id, 'name' => 'School owner', 'email' => $school->owner_email, 'password' => 'Billing-password-123']);
    centralAccountSession($this, 'institution_account', $account->id);
    $handoff = $this->post('https://euvion.com.ng/subscribe/portal')->assertOk()->assertSee('action="https://academics.mudiameuniversity.edu.ng/institution/enter"', false);
    preg_match('/name="token" value="([^"]+)"/', $handoff->getContent(), $match);
    $entry = $this->withHeader('Origin', 'https://euvion.com.ng')->post('https://academics.mudiameuniversity.edu.ng/institution/enter', ['token' => $match[1]])->assertRedirect('https://academics.mudiameuniversity.edu.ng/home');
    $cookie = $entry->getCookie('institution_'.$school->id.'_session');
    $this->withCookie($cookie->getName(), $cookie->getValue());
    $this->get('https://academics.mudiameuniversity.edu.ng/home')->assertOk()->assertSee('Manage Students')->assertDontSee('aria-label="Platform sidebar"', false);
    $this->post('https://academics.mudiameuniversity.edu.ng/dashboard/widgets/todos', ['title' => 'School dashboard task'])->assertRedirect();
    expect(DB::connection('central')->table('dashboard_todos')->where('tenant_id', $school->id)->where('title', 'School dashboard task')->exists())->toBeTrue();
    $this->get('https://euvion.com.ng/dashboard/widgets/todos')->assertNotFound();
});

it('rejects unsafe custom school hostnames and prevents domain ownership collisions', function () {
    $domains = \App\Services\Tenancy\InstitutionDomains::class;
    foreach (['https://academics.school.edu.ng', 'academics.school.edu.ng/path', 'academics.school.edu.ng:8000', '127.0.0.1', '*.school.edu.ng', 'educore.test', 'www.educore.test', 'admin.educore.test', 'dashboard.educore.test', 'other.nested.educore.test'] as $invalid) {
        expect(fn () => $domains::normalize($invalid))->toThrow(ValidationException::class);
    }
    expect($domains::normalize(' Academics.MudiameUniversity.edu.ng. '))->toBe('academics.mudiameuniversity.edu.ng');
    $provisioner = app(InstitutionProvisioner::class);
    $a = $provisioner->provision($provisioner->register(($this->institutionInput)('domain-a')), 'Secure-test-password-1');
    $b = $provisioner->provision($provisioner->register(($this->institutionInput)('domain-b')), 'Secure-test-password-1');
    $lifecycle = app(\App\Services\Tenancy\InstitutionLifecycle::class);
    $lifecycle->domain($a, 'academics.mudiameuniversity.edu.ng', 'Verified school hostname authorization', 1);
    expect(fn () => $lifecycle->domain($b, 'academics.mudiameuniversity.edu.ng', 'Attempt duplicate school hostname ownership', 1))->toThrow(ValidationException::class);
    expect($b->domains()->first()->domain)->toBe('domain-b.educore.test');
});

it('starts school branding after verified payment and hides platform controls in billing pages', function () {
    config(['tenancy.paystack_secret_key' => 'sk_test_fixture']);
    Plan::whereKey('gold')->update(['signup_enabled' => true]);
    $this->post('http://educore.test/subscribe/gold', institutionSignupInput('onboard-school'))->assertRedirect();
    $school = Institution::firstOrFail();
    $invoice = SubscriptionInvoice::firstOrFail();
    $payment = SubscriptionPayment::create(['invoice_id' => $invoice->id, 'reference' => 'onboarding-verified', 'email' => $school->owner_email]);
    $account = InstitutionAccount::firstOrFail();
    $sessionId = centralAccountSession($this, 'institution_account', $account->id);
    $platformAdmin = PlatformAdministrator::create(['name' => 'Platform owner', 'email' => 'platform@example.test', 'password' => 'Platform-password-123']);
    $payload = unserialize(base64_decode(DB::connection('central')->table('platform_sessions')->where('id', $sessionId)->value('payload')));
    $payload[Auth::guard('platform')->getName()] = $platformAdmin->id;
    DB::connection('central')->table('platform_sessions')->where('id', $sessionId)->update(['payload' => base64_encode(serialize($payload))]);
    $this->get('http://educore.test/subscribe')->assertOk()->assertDontSee('aria-label="Platform sidebar"', false);
    Http::fake(['api.paystack.co/*' => Http::response(['status' => true, 'data' => [
        'reference' => $payment->reference, 'id' => 76543, 'status' => 'success', 'amount' => $invoice->amount_minor,
        'currency' => 'NGN', 'domain' => 'test', 'customer' => ['email' => $school->owner_email]]])]);
    $this->get('http://educore.test/subscribe/callback?reference='.$payment->reference)->assertRedirect('http://educore.test/subscribe/onboarding');
    $this->get('http://educore.test/subscribe/onboarding')->assertOk()->assertSee('Make your school portal yours')->assertSee('Institution logo')->assertSee('Brand colour')->assertDontSee('aria-label="Platform sidebar"', false);
    $this->get('http://educore.test/subscribe')->assertRedirect('http://educore.test/subscribe/onboarding');
    expect($school->fresh()->subscription_status)->toBe('active');
});

it('saves school branding then signs into only its school using a single-use handoff', function () {
    Plan::whereKey('gold')->update(['signup_enabled' => true]);
    $this->post('http://educore.test/subscribe/gold', institutionSignupInput('brand-school'))->assertRedirect();
    $school = Institution::firstOrFail();
    app(\App\Services\Tenancy\InstitutionLifecycle::class)->domain($school, $school->domains()->first()->domain, 'School authorization DNS and HTTPS verified', 1);
    SubscriptionInvoice::firstOrFail()->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
    $school->forceFill(['subscription_status' => 'active'])->save();
    centralAccountSession($this, 'institution_account', InstitutionAccount::firstOrFail()->id);
    $this->withCredentials()->postJson('http://educore.test/subscribe/onboarding', ['name' => 'Brand School', 'primary_color' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors(['primary_color', 'logo']);
    $response = $this->post('http://educore.test/subscribe/onboarding', ['name' => 'Branded College', 'primary_color' => '#513cba',
        'logo' => \Illuminate\Http\UploadedFile::fake()->image('crest.png')])->assertOk()->assertSee('Opening Branded College');
    preg_match('/name="token" value="([^"]+)"/', $response->getContent(), $match);
    $token = $match[1];
    $school->refresh();
    expect($school->name)->toBe('Branded College')->and($school->settings['primary_color'])->toBe('#513cba')
        ->and($school->settings['onboarding_completed_at'])->not->toBeNull()
        ->and(DB::connection('central')->table('institution_portal_handoffs')->value('token_hash'))->toBe(hash('sha256', $token));
    app(TenantContext::class)->run($school, function () use ($school) {
        expect(Storage::disk('public')->exists($school->settings['logo_path']))->toBeTrue();
    });
    $other = app(InstitutionProvisioner::class)->provision(app(InstitutionProvisioner::class)->register(($this->institutionInput)('other-brand')), 'Other-school-password-1');
    $this->withHeader('Origin', 'http://educore.test:8000')->post('http://other-brand.educore.test/institution/enter', ['token' => $token])->assertForbidden();
    $entry = $this->post('http://portal.brand-school.edu.ng/institution/enter', ['token' => $token])->assertRedirect('http://portal.brand-school.edu.ng/home');
    $cookie = $entry->getCookie('institution_'.$school->id.'_session');
    $this->withCookie($cookie->getName(), $cookie->getValue());
    $this->get('http://portal.brand-school.edu.ng/home')->assertOk()->assertSee('Branded College')->assertSee('Manage Students')->assertDontSee('aria-label="Platform sidebar"', false);
    $this->post('http://portal.brand-school.edu.ng/institution/enter', ['token' => $token])->assertForbidden();
    expect(DB::connection('central')->table('institution_events')->where('event', 'onboarding_completed')->count())->toBe(1)
        ->and($other->fresh()->name)->not->toBe('Branded College');
});

it('rejects unpaid expired cross-origin and deactivated school entry without bypassing school two-factor', function () {
    Plan::whereKey('silver')->update(['signup_enabled' => true]);
    $this->post('http://educore.test/subscribe/silver', institutionSignupInput('entry-school'))->assertRedirect();
    $school = Institution::firstOrFail();
    app(\App\Services\Tenancy\InstitutionLifecycle::class)->domain($school, $school->domains()->first()->domain, 'School authorization DNS and HTTPS verified', 1);
    centralAccountSession($this, 'institution_account', InstitutionAccount::firstOrFail()->id);
    $this->post('http://educore.test/subscribe/onboarding', [])->assertForbidden();
    $this->get('http://educore.test/subscribe/onboarding')->assertRedirect('http://educore.test/subscribe');
    SubscriptionInvoice::firstOrFail()->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
    $school->forceFill(['subscription_status' => 'active', 'settings' => ['onboarding_completed_at' => now()->toIso8601String()]])->save();
    $onboarding = app(\App\Services\Tenancy\InstitutionOnboarding::class);
    $token = $onboarding->issue($school);
    $this->withHeader('Origin', 'https://untrusted.example')->post('http://portal.entry-school.edu.ng/institution/enter', ['token' => $token])->assertForbidden();
    DB::connection('central')->table('institution_portal_handoffs')->update(['expires_at' => now()->subMinute()]);
    $this->withHeader('Origin', 'http://educore.test:8000')->post('http://portal.entry-school.edu.ng/institution/enter', ['token' => $token])->assertForbidden();
    app(TenantContext::class)->run($school, function () {
        User::firstOrFail()->forceFill(['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_confirmed_at' => now()])->save();
    });
    $token = $onboarding->issue($school);
    $entry = $this->post('http://portal.entry-school.edu.ng/institution/enter', ['token' => $token])->assertRedirect('http://portal.entry-school.edu.ng/two-factor-challenge');
    $cookie = $entry->getCookie('institution_'.$school->id.'_session');
    $this->withCookie($cookie->getName(), $cookie->getValue());
    $this->get('http://portal.entry-school.edu.ng/home')->assertRedirect('http://portal.entry-school.edu.ng/login');
    $token = $onboarding->issue($school);
    app(TenantContext::class)->run($school, fn () => User::firstOrFail()->delete());
    $this->post('http://portal.entry-school.edu.ng/institution/enter', ['token' => $token])->assertForbidden();
});

it('requires branding before a paid school dashboard and keeps completed setup idempotent', function () {
    Plan::whereKey('silver')->update(['signup_enabled' => true]);
    $this->post('http://educore.test/subscribe/silver', institutionSignupInput('setup-gate'))->assertRedirect();
    $school = Institution::firstOrFail();
    app(\App\Services\Tenancy\InstitutionLifecycle::class)->domain($school, $school->domains()->first()->domain, 'School authorization DNS and HTTPS verified', 1);
    SubscriptionInvoice::firstOrFail()->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
    $school->forceFill(['subscription_status' => 'active'])->save();
    centralAccountSession($this, 'institution_account', InstitutionAccount::firstOrFail()->id);
    $login = $this->post('http://portal.setup-gate.edu.ng/login', ['email' => $school->owner_email, 'password' => 'Strong-signup-password-1'])->assertRedirect();
    $cookie = $login->getCookie('institution_'.$school->id.'_session');
    $this->withCookie($cookie->getName(), $cookie->getValue());
    $this->get('http://portal.setup-gate.edu.ng/home')->assertRedirect('http://educore.test:8000/subscribe/onboarding');
    $this->post('http://educore.test/subscribe/onboarding', ['name' => 'Finished College', 'primary_color' => '#123456',
        'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png')])->assertOk();
    $this->post('http://educore.test/subscribe/onboarding', ['name' => 'Retry must not rename', 'primary_color' => '#ffffff'])->assertOk();
    expect($school->fresh()->name)->toBe('Finished College')
        ->and(DB::connection('central')->table('institution_events')->where('event', 'onboarding_completed')->count())->toBe(1);
    $this->get('http://educore.test/subscribe')->assertOk()->assertSee('Renew for the next semester')->assertSee('Open school dashboard');
    $this->get('http://portal.setup-gate.edu.ng/home')->assertOk();
});

it('opens branding and the school dashboard immediately after payment without changing future invoice dates', function () {
    Plan::whereKey('silver')->update(['signup_enabled' => true]);
    $data = [...institutionSignupInput('future-setup'), 'semester_start' => '2026-10-25'];
    $this->post('http://educore.test/subscribe/silver', $data)->assertRedirect();
    $school = Institution::firstOrFail();
    app(\App\Services\Tenancy\InstitutionLifecycle::class)->domain($school, $school->domains()->first()->domain, 'School authorization DNS and HTTPS verified', 1);
    SubscriptionInvoice::firstOrFail()->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
    $school->forceFill(['subscription_status' => 'active'])->save();
    centralAccountSession($this, 'institution_account', InstitutionAccount::firstOrFail()->id);
    $this->get('http://educore.test/subscribe')->assertRedirect('http://educore.test/subscribe/onboarding');
    $this->get('http://educore.test/subscribe/onboarding')->assertOk()->assertSee('Make your school portal yours');
    $handoff = $this->post('http://educore.test/subscribe/onboarding', ['name' => 'Future College', 'primary_color' => '#123456',
        'logo' => \Illuminate\Http\UploadedFile::fake()->image('crest.png')])->assertOk()->assertSee('Opening Future College');
    expect($school->fresh()->settings['onboarding_completed_at'])->not->toBeNull()
        ->and(SubscriptionInvoice::firstOrFail()->semester_start->format('Y-m-d'))->toBe('2026-10-25')
        ->and(DB::connection('central')->table('institution_portal_handoffs')->count())->toBe(1);
    preg_match('/name="token" value="([^"]+)"/', $handoff->getContent(), $match);
    $entry = $this->withHeader('Origin', 'http://educore.test:8000')->post('http://portal.future-setup.edu.ng/institution/enter', ['token' => $match[1]])->assertRedirect('http://portal.future-setup.edu.ng/home');
    $cookie = $entry->getCookie('institution_'.$school->id.'_session');
    $this->withCookie($cookie->getName(), $cookie->getValue());
    $this->get('http://portal.future-setup.edu.ng/home')->assertOk()->assertSee('Manage Students');
});

it('retries interrupted signup setup using the saved institution account without duplicating invoices', function () {
    Plan::whereKey('silver')->update(['signup_enabled' => true]);
    $invalidRoot = $this->tenantTestRoot.'/blocked';
    file_put_contents($invalidRoot, 'file');
    config(['tenancy.storage_root' => $invalidRoot]);
    $this->post('http://educore.test/subscribe/silver', institutionSignupInput())->assertRedirect();
    $institution = Institution::firstOrFail();
    expect($institution->status)->toBe('failed');
    config(['tenancy.storage_root' => $this->tenantTestRoot.'/files']);
    $account = InstitutionAccount::firstOrFail();
    centralAccountSession($this, 'institution_account', $account->id);
    $this->post('http://educore.test/subscribe/provision', ['password' => 'wrong'])->assertForbidden();
    $this->post('http://educore.test/subscribe/provision', ['password' => 'Strong-signup-password-1'])->assertRedirect();
    expect($institution->fresh()->status)->toBe('ready')->and(SubscriptionInvoice::count())->toBe(1);
});

it('accepts signed subscription webhooks with server verification and tolerates duplicate delivery', function () {
    config(['tenancy.paystack_secret_key' => 'sk_test_fixture']);
    Plan::whereKey('silver')->update(['signup_enabled' => true]);
    $this->post('http://educore.test/subscribe/silver', institutionSignupInput())->assertRedirect();
    $invoice = SubscriptionInvoice::firstOrFail();
    $payment = SubscriptionPayment::create(['invoice_id' => $invoice->id, 'reference' => 'signed-reference', 'email' => 'owner@example.test']);
    Http::fake(['api.paystack.co/*' => Http::response(['status' => true, 'data' => [
        'reference' => $payment->reference, 'id' => 54321, 'status' => 'success', 'amount' => 2000000, 'currency' => 'NGN', 'domain' => 'test', 'customer' => ['email' => 'owner@example.test']]])]);
    $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference]]);
    $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $payload, 'sk_test_fixture')];
    for ($delivery = 0; $delivery < 2; $delivery++) {
        $this->call('POST', 'http://educore.test/subscribe/webhook', [], [], [], $headers, $payload)->assertOk();
    }
    expect($invoice->fresh()->status)->toBe('paid')->and(SubscriptionPayment::where('status', 'success')->count())->toBe(1);
});

it('rejects pending wrong-email wrong-currency and wrong-mode gateway responses', function () {
    config(['tenancy.paystack_secret_key' => 'sk_test_fixture']);
    Plan::whereKey('silver')->update(['signup_enabled' => true]);
    $this->post('http://educore.test/subscribe/silver', institutionSignupInput())->assertRedirect();
    $invoice = SubscriptionInvoice::firstOrFail();
    $payment = SubscriptionPayment::create(['invoice_id' => $invoice->id, 'reference' => 'mismatch-reference', 'email' => 'owner@example.test']);
    $valid = ['reference' => $payment->reference, 'id' => 11111, 'status' => 'success', 'amount' => 2000000, 'currency' => 'NGN', 'domain' => 'test', 'customer' => ['email' => 'owner@example.test']];
    foreach ([['status' => 'pending'], ['currency' => 'USD'], ['domain' => 'live'], ['customer' => ['email' => 'someone@example.test']]] as $change) {
        Http::fake(['api.paystack.co/*' => Http::response(['status' => true, 'data' => [...$valid, ...$change]])]);
        expect(fn () => app(SubscriptionPayments::class)->verify($payment))->toThrow(RuntimeException::class);
    }
    expect($invoice->fresh()->status)->toBe('pending')->and(Institution::first()->subscription_status)->toBe('awaiting_payment');
});

it('serves the central plan catalogue on local pricing with signup links and first-semester prices', function () {
    Plan::whereKey('silver')->update(['signup_enabled' => true]);
    $this->get('/pricing')->assertOk()->assertSee('₦1,000.00')->assertSee('₦2,250.00')->assertSee('₦3,250.00')
        ->assertSee('http://educore.test:8000/subscribe/silver', false)->assertSee('Student Record Management System')->assertSee('Deferred — excluded from this release');
    $this->get('http://educore.test/subscribe/silver')->assertOk();
    $this->get('http://educore.test/subscribe/gold')->assertNotFound();
});

it('registers a school with a locked first-semester enrollment invoice and blocks access until payment', function () {
    config(['session.driver' => 'database']);
    Plan::whereKey('silver')->update(['signup_enabled' => true]);
    $response = $this->post('http://educore.test/subscribe/silver', institutionSignupInput())->assertRedirect();
    $this->withCookie('educore_platform_session', $response->getCookie('educore_platform_session')->getValue());
    $this->get('http://educore.test/subscribe')->assertOk()->assertSee('₦20,000.00');
    $institution = Institution::where('slug', 'signup-school')->firstOrFail();
    $invoice = SubscriptionInvoice::firstOrFail();
    $account = InstitutionAccount::firstOrFail();
    expect($institution->status)->toBe('ready')->and($institution->subscription_status)->toBe('awaiting_payment')
        ->and($invoice->student_count)->toBe(20)->and($invoice->rate_minor)->toBe(100000)->and($invoice->amount_minor)->toBe(2000000)
        ->and(count($invoice->feature_snapshot))->toBe(8)->and(Hash::check('Strong-signup-password-1', $account->password))->toBeTrue();
    expect($institution->domains()->first()->domain)->toBe('portal.signup-school.edu.ng');
    expect($institution->settings['domain_pending_verification'])->toBeTrue();
    $this->get('http://portal.signup-school.edu.ng/login')->assertForbidden();
    $this->get('http://signup-school.educore.test/login')->assertNotFound();
    $this->postJson('http://educore.test/subscribe/silver', institutionSignupInput())->assertUnprocessable()->assertJsonValidationErrors('slug');
});

it('requires school-owned signup addresses and blocks entry until operator approval', function () {
    Plan::whereKey('silver')->update(['signup_enabled' => true]);
    foreach (['signup-school.educore.test', 'https://portal.school.edu.ng', 'educore.test'] as $domain) {
        $this->postJson('http://educore.test/subscribe/silver', [...institutionSignupInput(), 'domain' => $domain])
            ->assertUnprocessable()->assertJsonValidationErrors('domain');
    }
    expect(Institution::count())->toBe(0);
    $this->post('http://educore.test/subscribe/silver', institutionSignupInput())->assertRedirect();
    $school = Institution::firstOrFail();
    $school->forceFill(['subscription_status' => 'not_required', 'settings' => [...$school->settings, 'onboarding_completed_at' => now()->toIso8601String()]])->save();
    centralAccountSession($this, 'institution_account', InstitutionAccount::firstOrFail()->id);
    $this->post('http://educore.test/subscribe/portal')->assertOk()->assertSee('Your branding is saved');
    $this->get('http://portal.signup-school.edu.ng/login')->assertForbidden();
    expect(fn () => app(\App\Services\Tenancy\InstitutionOnboarding::class)->issue($school))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    app(\App\Services\Tenancy\InstitutionLifecycle::class)->domain($school, 'portal.signup-school.edu.ng', 'School authorization DNS and HTTPS verified', 1);
    expect($school->fresh()->settings['domain_pending_verification'])->toBeFalse();
    $this->get('http://portal.signup-school.edu.ng/login')->assertOk();
    $this->post('http://educore.test/subscribe/portal')->assertOk()->assertSee('portal.signup-school.edu.ng:8000/institution/enter', false);
});

it('rejects disabled plans and invalid enrollment before creating school records', function () {
    $this->post('http://educore.test/subscribe/silver', institutionSignupInput())->assertNotFound();
    Plan::whereKey('silver')->update(['signup_enabled' => true]);
    $this->postJson('http://educore.test/subscribe/silver', [...institutionSignupInput(), 'student_count' => 0])->assertUnprocessable()->assertJsonValidationErrors('student_count');
    expect(Institution::count())->toBe(0);
});

it('lets platform administrators create and edit plans while protecting existing invoice snapshots', function () {
    $admin = PlatformAdministrator::create(['name' => 'Platform Owner', 'email' => 'platform@example.test', 'password' => 'Strong-platform-password']);
    centralAccountSession($this, 'platform', $admin->id);
    $this->get('http://educore.test/dashboard')->assertOk()->assertSee('Awaiting payment');
    $this->get('http://educore.test/dashboard/plans/create')->assertOk();
    $this->withCredentials()->postJson('http://educore.test/dashboard/plans', ['code' => 'login', 'name' => 'Invalid Plan', 'standard_price' => '3000',
        'promotional_price' => '1500', 'features' => ['portal_control']])->assertUnprocessable()->assertJsonValidationErrors('code');
    $this->post('http://educore.test/dashboard/plans', ['code' => 'custom', 'name' => 'Custom Plan', 'standard_price' => '3000.25',
        'promotional_price' => '1500.50', 'signup_enabled' => '1', 'features' => ['portal_control']])->assertRedirect();
    expect(Plan::findOrFail('custom')->standard_rate_minor)->toBe(300025);
    $this->get('http://educore.test/dashboard/plans/custom/edit')->assertOk();
    $this->post('http://educore.test/subscribe/custom', institutionSignupInput('custom-school'))->assertRedirect();
    $invoice = SubscriptionInvoice::firstOrFail();
    centralAccountSession($this, 'platform', $admin->id);
    $this->put('http://educore.test/dashboard/plans/custom', ['name' => 'Changed Plan', 'standard_price' => '4000', 'promotional_price' => '2000', 'features' => ['student_records']])->assertRedirect();
    app(PlanCatalogue::class)->seed();
    expect($invoice->fresh()->rate_minor)->toBe(150050)->and($invoice->fresh()->plan_name)->toBe('Custom Plan')
        ->and($invoice->fresh()->feature_snapshot[0]['code'])->toBe('portal_control')
        ->and(Plan::findOrFail('custom')->features->pluck('code')->all())->toBe(['student_records']);
});

it('keeps institution billing accounts out of platform administration and other school callbacks', function () {
    Plan::whereKey('silver')->update(['signup_enabled' => true]);
    $this->post('http://educore.test/subscribe/silver', institutionSignupInput())->assertRedirect();
    $account = InstitutionAccount::firstOrFail();
    $sessionId = centralAccountSession($this, 'institution_account', $account->id);
    $this->get('http://educore.test/subscribe')->assertOk()->assertSee('₦20,000.00');
    $this->get('http://educore.test/dashboard')->assertRedirect('http://educore.test/dashboard/login');
    $this->get('http://educore.test/subscribe/callback?reference=another-school-payment')->assertNotFound();
    $this->post('http://educore.test/subscribe/checkout')->assertRedirect();
    $this->get('http://educore.test/subscribe')->assertSee('Institution subscription payments are not configured');
    $this->post('http://educore.test/subscribe/logout')->assertRedirect('http://educore.test/subscribe/login');
    expect(DB::connection('central')->table('platform_sessions')->where('id', $sessionId)->exists())->toBeFalse();
    $this->get('http://educore.test/subscribe')->assertRedirect('http://educore.test/subscribe/login');
});

it('initializes central checkout and verifies payment before enabling the portal idempotently', function () {
    config(['tenancy.paystack_secret_key' => 'sk_test_fixture']);
    Plan::whereKey('silver')->update(['signup_enabled' => true]);
    $this->post('http://educore.test/subscribe/silver', institutionSignupInput())->assertRedirect();
    $invoice = SubscriptionInvoice::firstOrFail();
    Http::fake([
        'api.paystack.co/transaction/initialize' => fn ($request) => Http::response(['status' => true, 'data' => ['reference' => $request['reference'], 'authorization_url' => 'https://checkout.paystack.com/fixture']]),
        'api.paystack.co/transaction/verify/*' => fn ($request) => Http::response(['status' => true, 'data' => [
            'reference' => basename($request->url()), 'id' => 12345, 'status' => 'success', 'amount' => 2000000, 'currency' => 'NGN', 'domain' => 'test', 'customer' => ['email' => 'owner@example.test']]]),
    ]);
    $service = app(SubscriptionPayments::class);
    expect($service->checkout($invoice))->toBe('https://checkout.paystack.com/fixture')->and($service->checkout($invoice))->toBe('https://checkout.paystack.com/fixture');
    Http::assertSent(fn ($request) => $request['amount'] === 2000000 && $request['callback_url'] === 'http://educore.test:8000/subscribe/callback');
    $payment = SubscriptionPayment::firstOrFail();
    $service->verify($payment);
    $service->verify($payment->fresh());
    expect($invoice->fresh()->status)->toBe('paid')->and(Institution::first()->subscription_status)->toBe('active')->and(SubscriptionPayment::count())->toBe(1);
    $this->get('http://portal.signup-school.edu.ng/login')->assertForbidden();
    $school = Institution::firstOrFail();
    app(\App\Services\Tenancy\InstitutionLifecycle::class)->domain($school, 'portal.signup-school.edu.ng', 'School authorization DNS and HTTPS verified', 1);
    $this->get('http://portal.signup-school.edu.ng/login')->assertOk();
});

it('rejects forged webhooks and mismatched payment amounts without activation', function () {
    config(['tenancy.paystack_secret_key' => 'sk_test_fixture']);
    Plan::whereKey('silver')->update(['signup_enabled' => true]);
    $this->post('http://educore.test/subscribe/silver', institutionSignupInput())->assertRedirect();
    $invoice = SubscriptionInvoice::firstOrFail();
    $payment = SubscriptionPayment::create(['invoice_id' => $invoice->id, 'reference' => 'fixture-reference', 'email' => 'owner@example.test']);
    $this->postJson('http://educore.test/subscribe/webhook', ['event' => 'charge.success', 'data' => ['reference' => $payment->reference]])->assertUnauthorized();
    Http::fake(['api.paystack.co/*' => Http::response(['status' => true, 'data' => [
        'reference' => $payment->reference, 'id' => 12345, 'status' => 'success', 'amount' => 100, 'currency' => 'NGN', 'domain' => 'test', 'customer' => ['email' => 'owner@example.test']]])]);
    expect(fn () => app(SubscriptionPayments::class)->verify($payment))->toThrow(RuntimeException::class);
    expect($invoice->fresh()->status)->toBe('pending')->and(Institution::first()->subscription_status)->toBe('awaiting_payment');
});

it('shows institution monitoring and lets only the platform admin suspend and resume access', function () {
    $provisioner = app(InstitutionProvisioner::class);
    $institution = $provisioner->provision($provisioner->register(($this->institutionInput)('monitor')), 'Secure-test-password-1');
    $admin = PlatformAdministrator::create(['name' => 'Owner', 'email' => 'platform@example.test', 'password' => 'Strong-platform-password']);
    centralAccountSession($this, 'platform', $admin->id);
    $this->get('http://educore.test/dashboard/institutions/'.$institution->id)->assertOk()->assertSee('Provisioning history')->assertSee('Current student accounts');
    $this->patch('http://educore.test/dashboard/institutions/'.$institution->id.'/status', ['status' => 'suspended', 'reason' => 'Scheduled institution maintenance'])->assertRedirect();
    $this->get('http://monitor.educore.test/login')->assertForbidden();
    $this->patch('http://educore.test/dashboard/institutions/'.$institution->id.'/status', ['status' => 'ready', 'reason' => 'Institution maintenance completed'])->assertRedirect();
    $this->get('http://monitor.educore.test/login')->assertOk();
});

it('stores agreed prices and first-semester promotion with inherited features', function () {
    $silver = Plan::findOrFail('silver');
    $gold = Plan::findOrFail('gold');
    $platinum = Plan::findOrFail('platinum');
    expect($silver->standard_rate_minor)->toBe(200000)->and($silver->promotional_rate_minor)->toBe(100000)
        ->and($gold->standard_rate_minor)->toBe(450000)->and($gold->promotional_rate_minor)->toBe(225000)
        ->and($platinum->standard_rate_minor)->toBe(650000)->and($platinum->promotional_rate_minor)->toBe(325000)
        ->and($platinum->promotion_semesters)->toBe(1)
        ->and(config('tenancy.billing.billable_student_basis'))->toBe('enrolled')
        ->and(config('tenancy.billing.student_count_census'))->toBe('semester_start')
        ->and($silver->features()->count())->toBe(9)->and($gold->features()->count())->toBe(13)
        ->and($platinum->features()->count())->toBe(18)->and($platinum->signup_enabled)->toBeFalse();
    $silver->update(['standard_rate_minor' => 210000]);
    app(PlanCatalogue::class)->seed();
    expect($silver->fresh()->standard_rate_minor)->toBe(210000);
});

it('provisions two schools in one database with separate users, settings and teams', function () {
    $provisioner = app(InstitutionProvisioner::class);
    $alpha = $provisioner->provision($provisioner->register(($this->institutionInput)('alpha')), 'Secure-test-password-1');
    $beta = $provisioner->provision($provisioner->register(($this->institutionInput)('beta', 'gold')), 'Secure-test-password-2');
    $context = app(TenantContext::class);
    $alphaAdminId = $context->run($alpha, function () use ($alpha) {
        $admin = User::firstOrFail();
        expect(User::count())->toBe(1)->and($admin->tenant_id)->toBe($alpha->id)
            ->and(Hash::check('Secure-test-password-1', $admin->password))->toBeTrue()
            ->and($admin->ownedTeams()->count())->toBe(1)
            ->and(RegistrationSetting::current()->tenant_id)->toBe($alpha->id)
            ->and(PromotionPolicy::current()->tenant_id)->toBe($alpha->id)
            ->and(Plan::count())->toBe(3);
        $admin->update(['name' => 'Alpha only']);

        return $admin->id;
    });
    $context->run($beta, function () use ($alphaAdminId, $beta) {
        expect(User::count())->toBe(1)->and(User::first()->name)->toBe('School Administrator')
            ->and(User::find($alphaAdminId))->toBeNull()
            ->and(RegistrationSetting::current()->tenant_id)->toBe($beta->id)
            ->and(PromotionPolicy::current()->tenant_id)->toBe($beta->id)
            ->and(DB::connection()->getDatabaseName())->toBe(DB::connection('central')->getDatabaseName());
    });
    expect(DB::getDefaultConnection())->toBe('sqlite')->and(app(TenantState::class)->id())->toBeNull()
        ->and(DB::connection('central')->table('users')->count())->toBe(2)
        ->and(DB::connection('central')->getSchemaBuilder()->hasColumn('courses', 'tenant_id'))->toBeTrue()
        ->and(DB::connection('sqlite')->getSchemaBuilder()->hasTable('institutions'))->toBeFalse();
});

it('preserves passwords and avoids duplicate seed records on repeated provisioning', function () {
    $provisioner = app(InstitutionProvisioner::class);
    $input = ($this->institutionInput)('repeat');
    $institution = $provisioner->provision($provisioner->register($input), 'Original-password-1');
    $again = $provisioner->provision($provisioner->register($input), 'Different-password-2');
    expect($again->id)->toBe($institution->id)->and(Institution::count())->toBe(1)
        ->and(DB::connection('central')->table('provisioning_attempts')->count())->toBe(1);
    app(TenantContext::class)->run($again, function () {
        expect(User::count())->toBe(1)->and(Hash::check('Original-password-1', User::first()->password))->toBeTrue();
    });
});

it('records setup failure and resumes without resetting seeded credentials', function () {
    $storage = config('tenancy.storage_root');
    File::put($this->tenantTestRoot.'/blocked', 'Cannot create a directory here');
    config(['tenancy.storage_root' => $this->tenantTestRoot.'/blocked']);
    $provisioner = app(InstitutionProvisioner::class);
    $institution = $provisioner->register(($this->institutionInput)('retry'));
    expect(fn () => $provisioner->provision($institution, 'Original-password-1'))->toThrow(ErrorException::class);
    expect($institution->fresh()->status)->toBe('failed')->and($institution->fresh()->failure_code)->toBe('storage_failed')
        ->and(DB::getDefaultConnection())->toBe('sqlite')->and(app(TenantState::class)->id())->toBeNull();
    config(['tenancy.storage_root' => $storage]);
    $restored = $provisioner->provision($institution->fresh(), 'Different-password-2');
    expect($restored->status)->toBe('ready')->and(DB::connection('central')->table('provisioning_attempts')->count())->toBe(2);
    app(TenantContext::class)->run($restored, function () {
        expect(User::count())->toBe(1)->and(Hash::check('Original-password-1', User::first()->password))->toBeTrue();
    });
});

it('rejects central domains, duplicate domains and inconsistent retry details', function () {
    $provisioner = app(InstitutionProvisioner::class);
    $input = ($this->institutionInput)('domain');
    expect(fn () => $provisioner->register([...$input, 'domain' => 'educore.test']))->toThrow(RuntimeException::class);
    $provisioner->register($input);
    expect(fn () => $provisioner->register([...$input, 'slug' => 'another']))->toThrow(RuntimeException::class)
        ->and(fn () => $provisioner->register([...$input, 'owner_email' => 'different@example.test']))->toThrow(RuntimeException::class);
});

it('restores context after failures and blocks pending, nested and stale suspended access', function () {
    $provisioner = app(InstitutionProvisioner::class);
    $institution = $provisioner->register(($this->institutionInput)('context'));
    $context = app(TenantContext::class);
    expect(fn () => $context->run($institution, fn () => null))->toThrow(RuntimeException::class);
    $ready = $provisioner->provision($institution, 'Secure-test-password-1');
    expect(fn () => $context->run($ready, fn () => throw new RuntimeException('Callback failed')))->toThrow(RuntimeException::class)
        ->and(DB::getDefaultConnection())->toBe('sqlite')->and(app(TenantState::class)->id())->toBeNull();
    $context->run($ready, function () use ($context, $ready) {
        expect(fn () => $context->run($ready, fn () => null))->toThrow(RuntimeException::class);
    });
    Institution::whereKey($ready->id)->update(['status' => 'suspended']);
    expect(fn () => $context->run($ready, fn () => null))->toThrow(RuntimeException::class);
});

it('fails closed for shared user queries without an institution and prevents cross-school model updates', function () {
    expect(fn () => User::on('central')->count())->toThrow(RuntimeException::class);
    config(['database.connections.shared_alias' => config('database.connections.central')]);
    expect(fn () => User::on('shared_alias')->count())->toThrow(RuntimeException::class);
    DB::purge('shared_alias');
    $provisioner = app(InstitutionProvisioner::class);
    $alpha = $provisioner->provision($provisioner->register(($this->institutionInput)('alpha')), 'Secure-test-password-1');
    $beta = $provisioner->provision($provisioner->register(($this->institutionInput)('beta')), 'Secure-test-password-2');
    $context = app(TenantContext::class);
    $alphaUser = $context->run($alpha, fn () => User::firstOrFail());
    $context->run($beta, function () use ($alphaUser) {
        expect(fn () => $alphaUser->update(['name' => 'Unauthorized']))->toThrow(RuntimeException::class);
        $wrong = new User(['name' => 'Wrong school', 'email' => 'wrong@example.test', 'password' => 'test-password']);
        $wrong->forceFill(['tenant_id' => $alphaUser->tenant_id]);
        expect(fn () => $wrong->save())->toThrow(RuntimeException::class);
    });
});

it('reinitializes the shared platform without migrating the legacy school or losing records', function () {
    $this->artisan('tenancy:install')->assertSuccessful();
    expect(Plan::count())->toBe(3)->and(DB::connection('sqlite')->getSchemaBuilder()->hasTable('institutions'))->toBeFalse();
});

it('refuses databases without the shared-platform ownership marker', function () {
    DB::connection('central')->table('tenancy_schema')->update(['architecture' => 'unrelated']);
    $this->artisan('tenancy:install')->assertFailed();
    expect(DB::connection('central')->getSchemaBuilder()->hasTable('users'))->toBeTrue();
});

it('isolates academic raw queries including aliases joins OR conditions and foreign links', function () {
    $provisioner = app(InstitutionProvisioner::class);
    $alpha = $provisioner->provision($provisioner->register(($this->institutionInput)('alpha')), 'Secure-test-password-1');
    $beta = $provisioner->provision($provisioner->register(($this->institutionInput)('beta')), 'Secure-test-password-2');
    $context = app(TenantContext::class);
    $alphaFaculty = $context->run($alpha, fn () => Faculty::create(['name' => 'Alpha faculty', 'code' => 'SCI']));
    $context->run($beta, function () use ($alphaFaculty) {
        Faculty::create(['name' => 'Beta faculty', 'code' => 'SCI']);
        expect(DB::table('faculties as f')->where('f.name', 'missing')->orWhere('f.code', 'SCI')->count())->toBe(1)
            ->and(DB::table('faculties')->where('id', $alphaFaculty->id)->exists())->toBeFalse()
            ->and(Faculty::withoutGlobalScopes()->count())->toBe(1)
            ->and(Validator::make(['faculty_id' => $alphaFaculty->id], ['faculty_id' => 'exists:faculties,id'])->fails())->toBeTrue();
        expect(fn () => DB::table('departments')->insert(['name' => 'Cross-school', 'faculty_id' => $alphaFaculty->id]))
            ->toThrow(ValidationException::class);
        DB::table('users')->where('name', 'missing')->orWhere('id', $alphaFaculty->id)->update(['name' => 'Scoped update']);
        expect(DB::table('users as u')->join('teams as t', 't.user_id', '=', 'u.id')->count())->toBe(1);
        expect(fn () => DB::table('faculties')->truncate())->toThrow(RuntimeException::class);
        expect(fn () => DB::table('faculties')->update(['tenant_id' => null]))->toThrow(ValidationException::class);
    });
    $context->run($alpha, fn () => expect(User::first()->name)->toBe('School Administrator'));
});

it('isolates files caches branding and settings and restores them after context exit', function () {
    $provisioner = app(InstitutionProvisioner::class);
    $alpha = $provisioner->provision($provisioner->register(($this->institutionInput)('alpha')), 'Secure-test-password-1');
    $beta = $provisioner->provision($provisioner->register(($this->institutionInput)('beta')), 'Secure-test-password-2');
    $context = app(TenantContext::class);
    $previousName = config('app.name');
    $context->run($alpha, function () {
        Storage::disk('local')->put('same.txt', 'Alpha private');
        Storage::disk('public')->put('same.txt', 'Alpha public');
        Cache::put('same-key', 'Alpha cache');
        expect(config('app.name'))->toBe('Alpha Institution')->and(config('session.domain'))->toBeNull();
    });
    $context->run($beta, function () {
        expect(Storage::disk('local')->exists('same.txt'))->toBeFalse()
            ->and(Storage::disk('public')->exists('same.txt'))->toBeFalse()
            ->and(Cache::get('same-key'))->toBeNull();
        Cache::put('same-key', 'Beta cache');
        Cache::flush();
    });
    $context->run($alpha, function () {
        expect(Storage::disk('local')->get('same.txt'))->toBe('Alpha private')
            ->and(Cache::get('same-key'))->toBe('Alpha cache');
    });
    expect(config('app.name'))->toBe($previousName)->and(config('cache.default'))->toBe('array');
});

it('resolves tenant domains rejects unknown suspended and central school routes and separates platform login', function () {
    $provisioner = app(InstitutionProvisioner::class);
    $alpha = $provisioner->provision($provisioner->register(($this->institutionInput)('alpha')), 'Secure-test-password-1');
    $this->get('http://alpha.educore.test/login')->assertOk()->assertSee('Alpha Institution');
    $this->get('http://unknown.educore.test/login')->assertNotFound();
    $this->get('http://educore.test/login')->assertNotFound();
    $this->get('http://educore.test/dashboard/login')->assertOk();
    $this->get('http://alpha.educore.test/dashboard/login')->assertForbidden();
    $this->get('http://alpha.educore.test/admin/backups')->assertForbidden();
    Institution::whereKey($alpha->id)->update(['status' => 'suspended']);
    $this->get('http://alpha.educore.test/login')->assertForbidden();
});

it('isolates bearer tokens between institutions with the same administrator email', function () {
    $provisioner = app(InstitutionProvisioner::class);
    $alpha = $provisioner->provision($provisioner->register(($this->institutionInput)('alpha')), 'Secure-test-password-1');
    $provisioner->provision($provisioner->register(($this->institutionInput)('beta')), 'Secure-test-password-2');
    $token = app(TenantContext::class)->run($alpha, fn () => User::first()->createToken('isolation')->plainTextToken);
    $this->withToken($token)->getJson('http://alpha.educore.test/api/user')->assertOk()->assertJsonPath('tenant_id', $alpha->id);
    $this->withToken($token)->getJson('http://beta.educore.test/api/user')->assertUnauthorized();
});

it('keeps password reset tokens separate for identical school email addresses', function () {
    $provisioner = app(InstitutionProvisioner::class);
    $alpha = $provisioner->provision($provisioner->register(($this->institutionInput)('alpha')), 'Secure-test-password-1');
    $beta = $provisioner->provision($provisioner->register(($this->institutionInput)('beta')), 'Secure-test-password-2');
    $context = app(TenantContext::class);
    $alphaToken = $context->run($alpha, fn () => Password::broker()->createToken(User::first()));
    $context->run($beta, function () use ($alphaToken) {
        expect(Password::broker()->tokenExists(User::first(), $alphaToken))->toBeFalse();
        Password::broker()->createToken(User::first());
    });
    $context->run($alpha, fn () => expect(Password::broker()->tokenExists(User::first(), $alphaToken))->toBeTrue());
});

it('authenticates school passwords and persists tenant-only browser sessions', function () {
    config(['session.driver' => 'database']);
    $provisioner = app(InstitutionProvisioner::class);
    $alpha = $provisioner->provision($provisioner->register(($this->institutionInput)('alpha')), 'Secure-test-password-1');
    $beta = $provisioner->provision($provisioner->register(($this->institutionInput)('beta')), 'Secure-test-password-2');
    $this->post('http://alpha.educore.test/login', ['email' => 'admin@example.test', 'password' => 'Secure-test-password-1'])->assertRedirect();
    $alphaSession = DB::connection('central')->table('institution_sessions')->where('tenant_id', $alpha->id)->whereNotNull('user_id')->first();
    expect($alphaSession)->not->toBeNull();
    $this->post('http://beta.educore.test/login', ['email' => 'admin@example.test', 'password' => 'Secure-test-password-1'])->assertRedirect();
    expect(DB::connection('central')->table('institution_sessions')->where('tenant_id', $beta->id)->whereNotNull('user_id')->count())->toBe(0);
});

it('allows the same browser session identifier in separate institutions without sharing payloads', function () {
    $provisioner = app(InstitutionProvisioner::class);
    $alpha = $provisioner->provision($provisioner->register(($this->institutionInput)('alpha')), 'Secure-test-password-1');
    $beta = $provisioner->provision($provisioner->register(($this->institutionInput)('beta')), 'Secure-test-password-2');
    $context = app(TenantContext::class);
    $id = str_repeat('a', 40);
    $context->run($alpha, fn () => DB::table('institution_sessions')->insert(['id' => $id, 'payload' => 'Alpha', 'last_activity' => time()]));
    $context->run($beta, function () use ($id) {
        expect(DB::table('institution_sessions')->where('id', $id)->exists())->toBeFalse();
        DB::table('institution_sessions')->insert(['id' => $id, 'payload' => 'Beta', 'last_activity' => time()]);
    });
    $context->run($alpha, fn () => expect(DB::table('institution_sessions')->where('id', $id)->value('payload'))->toBe('Alpha'));
});

class TenantQueuedUserProbe implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user, public bool $fail = false) {}

    public function handle(): void
    {
        if ($this->fail) {
            throw new RuntimeException('Expected queue failure');
        }
        $this->user->update(['name' => 'Queued '.$this->user->tenant_id]);
    }

    public function failed(Throwable $exception): void
    {
        $this->user->update(['name' => 'Failed '.$this->user->tenant_id]);
    }
}

it('restores institution context before queued models are deserialized and clears it between jobs', function () {
    config(['queue.default' => 'database']);
    $provisioner = app(InstitutionProvisioner::class);
    $alpha = $provisioner->provision($provisioner->register(($this->institutionInput)('alpha')), 'Secure-test-password-1');
    $beta = $provisioner->provision($provisioner->register(($this->institutionInput)('beta')), 'Secure-test-password-2');
    foreach ([$alpha, $beta] as $institution) {
        app(TenantContext::class)->run($institution, fn () => Bus::dispatch(new TenantQueuedUserProbe(User::first())));
    }
    foreach ([$alpha, $beta] as $institution) {
        $job = app('queue')->connection('institutions')->pop('institution_'.$institution->id);
        expect($job)->not->toBeNull()->and($job->payload()['institution_id'])->toBe($institution->id);
        app('queue.worker')->process('institutions', $job, new WorkerOptions);
        expect(app(TenantState::class)->id())->toBeNull()->and(DB::getDefaultConnection())->toBe('sqlite');
        app(TenantContext::class)->run($institution, fn () => expect(User::first()->name)->toBe('Queued '.$institution->id));
    }
});

it('isolates database cache flushes and locks across institutions', function () {
    config(['cache.default' => 'database']);
    $provisioner = app(InstitutionProvisioner::class);
    $alpha = $provisioner->provision($provisioner->register(($this->institutionInput)('alpha')), 'Secure-test-password-1');
    $beta = $provisioner->provision($provisioner->register(($this->institutionInput)('beta')), 'Secure-test-password-2');
    $context = app(TenantContext::class);
    $context->run($alpha, function () {
        Cache::put('same', 'Alpha');
        expect(Cache::lock('same-lock', 60)->get())->toBeTrue();
    });
    $context->run($beta, function () {
        expect(Cache::get('same'))->toBeNull()
            ->and(Cache::lock('same-lock', 60)->get())->toBeTrue();
        Cache::flush();
    });
    $context->run($alpha, fn () => expect(Cache::get('same'))->toBe('Alpha'));
});

it('keeps failed job callbacks in the institution and clears their context afterward', function () {
    config(['queue.default' => 'database']);
    $provisioner = app(InstitutionProvisioner::class);
    $institution = $provisioner->provision($provisioner->register(($this->institutionInput)('failure')), 'Secure-test-password-1');
    app(TenantContext::class)->run($institution, fn () => Bus::dispatch(new TenantQueuedUserProbe(User::first(), true)));
    $job = app('queue')->connection('institutions')->pop('institution_'.$institution->id);
    expect(fn () => app('queue.worker')->process('institutions', $job, new WorkerOptions))->toThrow(RuntimeException::class);
    expect(app(TenantState::class)->id())->toBeNull()->and(DB::getDefaultConnection())->toBe('sqlite');
    app(TenantContext::class)->run($institution, fn () => expect(User::first()->name)->toBe('Failed '.$institution->id));
});


it('enforces all three tier permissions on direct requests and database writes', function (string $tier, bool $results, bool $cbt) {
    $provisioner = app(InstitutionProvisioner::class);
        $institution = $provisioner->provision($provisioner->register(($this->institutionInput)($tier, $tier)), 'Secure-test-password-1');
        app(TenantContext::class)->run($institution, function () use ($results, $cbt) {
            $permissions = app(\App\Services\Tenancy\FeaturePermissions::class);
            expect($permissions->allows('result_management'))->toBe($results)
                ->and($permissions->allows('cbt'))->toBe($cbt)
                ->and($permissions->allows('attendance'))->toBe($cbt)
                ->and($permissions->allows('e_learning'))->toBe($cbt)
                ->and($permissions->allows('wallet'))->toBeFalse()
                ->and($permissions->allows('user_management'))->toBeTrue();
            if (! $cbt) {
                expect(fn () => DB::table('tests')->insert(['name' => 'Forbidden test']))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
                expect(fn () => DB::table('tests')->delete())->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
            }
        });
        $token = app(TenantContext::class)->run($institution, fn () => User::first()->createToken('feature-check')->plainTextToken);
        $this->withToken($token);
        if (! $results) {
            $this->getJson('http://'.$tier.'.educore.test/academic')->assertForbidden();
            $this->postJson('http://'.$tier.'.educore.test/academic/batch', [])->assertForbidden();
        }
        if (! $cbt) {
            app(TenantContext::class)->run($institution, function () {
                Storage::disk('public')->put('course_materials/example.pdf', 'Protected course material');
                expect(fn () => app(\App\Http\Controllers\InstitutionSettingsController::class)->asset('course_materials/example.pdf'))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
            });
            $this->getJson('http://'.$tier.'.educore.test/admin/tests')->assertForbidden();
            $this->postJson('http://'.$tier.'.educore.test/admin/tests', [])->assertForbidden();
            $this->getJson('http://'.$tier.'.educore.test/attendance/scan/token')->assertForbidden();
        }
})->with([['silver', false, false], ['gold', true, false], ['platinum', true, true]]);

it('uses paid snapshots and switches plans only at the next paid semester boundary', function () {
    Plan::query()->update(['signup_enabled' => true]);
    $provisioner = app(InstitutionProvisioner::class);
    $school = $provisioner->provision($provisioner->register(($this->institutionInput)('semester-school')), 'Secure-test-password-1');
    $school->forceFill(['subscription_status' => 'active'])->save();
    $billing = app(\App\Services\Tenancy\SemesterBilling::class);
    $first = $billing->issue($school, Plan::findOrFail('silver'), institutionSignupInput());
    $first->update(['status' => 'paid', 'paid_at' => now()]);
    $nextData = ['semester_name' => 'Second', 'semester_start' => '2027-02-01', 'semester_end' => '2027-06-30', 'student_count' => 30];
    $next = $billing->issue($school, Plan::findOrFail('gold'), $nextData);
    expect($next->amount_minor)->toBe(13500000)->and($next->promotional)->toBeFalse()
        ->and($billing->issue($school, Plan::findOrFail('gold'), $nextData)->id)->toBe($next->id);
    $next->update(['status' => 'paid', 'paid_at' => now()]);
    Plan::findOrFail('silver')->features()->attach('cbt');
    app(TenantContext::class)->run($school, fn () => expect(app(\App\Services\Tenancy\FeaturePermissions::class)->allows('result_management'))->toBeFalse()
        ->and(app(\App\Services\Tenancy\FeaturePermissions::class)->allows('cbt'))->toBeFalse());
    $this->travelTo(\Illuminate\Support\Carbon::parse('2027-02-01 12:00:00 UTC'));
    app(TenantContext::class)->run($school, fn () => expect(app(\App\Services\Tenancy\FeaturePermissions::class)->allows('result_management'))->toBeTrue()
        ->and(app(\App\Services\Tenancy\FeaturePermissions::class)->allows('cbt'))->toBeFalse());
    $this->travelTo(\Illuminate\Support\Carbon::parse('2027-07-01 12:00:00 UTC'));
    $this->get('http://semester-school.educore.test/login')->assertForbidden();
    expect(SubscriptionInvoice::count())->toBe(2)->and($first->fresh()->student_count)->toBe(20);
});

it('rejects overlapping semesters altered retries and edits to fixed invoice amounts', function () {
    Plan::query()->update(['signup_enabled' => true]);
    $provisioner = app(InstitutionProvisioner::class);
    $school = $provisioner->provision($provisioner->register(($this->institutionInput)('fixed-school')), 'Secure-test-password-1');
    $billing = app(\App\Services\Tenancy\SemesterBilling::class);
    $plan = Plan::findOrFail('silver');
    $first = $billing->issue($school, $plan, institutionSignupInput());
    expect(fn () => $first->update(['amount_minor' => 1]))->toThrow(RuntimeException::class);
    expect(fn () => $billing->issue($school, $plan, [...institutionSignupInput(), 'student_count' => 99]))->toThrow(ValidationException::class);
    expect(fn () => $billing->issue($school, $plan, [...institutionSignupInput(), 'semester_start' => '2026-11-01']))->toThrow(ValidationException::class);
    $first->refresh()->update(['status' => 'paid']);
    expect(fn () => $billing->issue($school, $plan, [...institutionSignupInput(), 'semester_start' => '2027-01-31', 'semester_end' => '2027-06-30']))->toThrow(ValidationException::class);
    expect(SubscriptionInvoice::count())->toBe(1);
});

it('never revives an old open ended package after a later unpaid or expired semester', function () {
    Plan::query()->update(['signup_enabled' => true]);
    $provisioner = app(InstitutionProvisioner::class);
    $school = $provisioner->provision($provisioner->register(($this->institutionInput)('legacy-package')), 'Secure-test-password-1');
    $school->forceFill(['subscription_status' => 'active'])->save();
    $billing = app(\App\Services\Tenancy\SemesterBilling::class);
    $first = $billing->issue($school, Plan::findOrFail('gold'), institutionSignupInput());
    // Simulate a pre-upgrade invoice without altering an issued invoice through the application.
    DB::connection('central')->table('subscription_invoices')->where('id', $first->id)->update(['semester_end' => null, 'status' => 'paid']);
    $renewal = $billing->issue($school, Plan::findOrFail('silver'), [
        'semester_name' => 'Next', 'semester_start' => '2027-02-01', 'semester_end' => '2027-06-30', 'student_count' => 20,
    ]);
    expect($billing->current($school)->id)->toBe($first->id);
    $this->travelTo(\Illuminate\Support\Carbon::parse('2027-02-01 12:00:00 UTC'));
    expect($billing->accessible($school))->toBeFalse();
    $renewal->update(['status' => 'paid']);
    expect($billing->current($school)->id)->toBe($renewal->id);
    $this->travelTo(\Illuminate\Support\Carbon::parse('2027-07-01 12:00:00 UTC'));
    expect($billing->accessible($school))->toBeFalse()->and($billing->status($school))->toBe('expired');
});

it('keeps billing available after expiry and creates renewals for only the signed in institution', function () {
    Plan::query()->update(['signup_enabled' => true]);
    $this->post('http://educore.test/subscribe/silver', institutionSignupInput())->assertRedirect();
    $school = Institution::firstOrFail();
    app(\App\Services\Tenancy\InstitutionLifecycle::class)->domain($school, 'portal.signup-school.edu.ng', 'School authorization DNS and HTTPS verified', 1);
    SubscriptionInvoice::firstOrFail()->update(['status' => 'paid']);
    $school->forceFill(['subscription_status' => 'active'])->save();
    $this->travelTo(\Illuminate\Support\Carbon::parse('2027-02-01 12:00:00 UTC'));
    centralAccountSession($this, 'institution_account', InstitutionAccount::firstOrFail()->id);
    $this->get('http://portal.signup-school.edu.ng/login')->assertForbidden();
    $this->get('http://educore.test/subscribe')->assertOk()->assertSee('Renew for the next semester');
    $data = ['plan_code' => 'gold', 'semester_name' => 'Second', 'semester_start' => '2027-02-01', 'semester_end' => '2027-06-30', 'student_count' => 25, 'confirm_census' => 1, 'institution_id' => 'forged-school-id'];
    $this->post('http://educore.test/subscribe/renew', $data)->assertRedirect();
    $next = SubscriptionInvoice::orderByDesc('semester_start')->firstOrFail();
    expect($next->institution_id)->toBe($school->id)->and($next->amount_minor)->toBe(11250000)->and($next->promotional)->toBeFalse();
    $this->get('http://educore.test/subscribe')->assertOk()->assertSee('Standard rate per student')->assertSee('Second');
    $this->post('http://educore.test/subscribe/renew', $data)->assertRedirect();
    expect(SubscriptionInvoice::count())->toBe(2);
});

it('requires audited expiring platform overrides and never overrides unpaid access', function () {
    $provisioner = app(InstitutionProvisioner::class);
    $school = $provisioner->provision($provisioner->register(($this->institutionInput)('override-school')), 'Secure-test-password-1');
    $admin = PlatformAdministrator::create(['name' => 'Platform Owner', 'email' => 'platform@example.test', 'password' => 'Strong-platform-password']);
    centralAccountSession($this, 'platform', $admin->id);
    $url = 'http://educore.test/dashboard/institutions/'.$school->id.'/features';
    $data = ['feature_code' => 'cbt', 'allowed' => 1, 'reason' => 'Temporary institution evaluation', 'expires_at' => now()->addDay()->toDateTimeString()];
    $this->post($url, $data)->assertRedirect();
    $this->get('http://educore.test/dashboard/institutions/'.$school->id)->assertOk()->assertSee('Temporary institution evaluation');
    app(TenantContext::class)->run($school, fn () => expect(app(\App\Services\Tenancy\FeaturePermissions::class)->allows('cbt'))->toBeTrue());
    $school->forceFill(['subscription_status' => 'awaiting_payment'])->save();
    app(TenantContext::class)->run($school, fn () => expect(app(\App\Services\Tenancy\FeaturePermissions::class)->allows('cbt'))->toBeFalse());
    $school->forceFill(['subscription_status' => 'not_required'])->save();
    $this->travel(2)->days();
    app(TenantContext::class)->run($school, fn () => expect(app(\App\Services\Tenancy\FeaturePermissions::class)->allows('cbt'))->toBeFalse());
    expect(DB::connection('central')->table('subscription_events')->where('event', 'feature_override')->count())->toBe(1);
});


it('completes verified signup and renders plan aware dashboards for every tier', function (string $tier, int $rate) {
    config(['tenancy.paystack_secret_key' => 'sk_test_fixture']);
    Plan::whereKey($tier)->update(['signup_enabled' => true]);
    $this->post('http://educore.test/subscribe/'.$tier, institutionSignupInput('tier-signup'))->assertRedirect();
    $school = Institution::firstOrFail();
    app(\App\Services\Tenancy\InstitutionLifecycle::class)->domain($school, 'portal.tier-signup.edu.ng', 'School authorization DNS and HTTPS verified', 1);
    $invoice = SubscriptionInvoice::firstOrFail();
    expect($invoice->rate_minor)->toBe($rate)->and($invoice->amount_minor)->toBe(20 * $rate)
        ->and(strlen($invoice->price_version))->toBe(64)->and($invoice->semester_end->format('Y-m-d'))->toBe('2027-01-31');
    Http::fake([
        'api.paystack.co/transaction/initialize' => fn ($request) => Http::response(['status' => true, 'data' => ['reference' => $request['reference'], 'authorization_url' => 'https://checkout.paystack.com/tier-fixture']]),
        'api.paystack.co/transaction/verify/*' => fn ($request) => Http::response(['status' => true, 'data' => [
            'reference' => basename($request->url()), 'id' => 24680, 'status' => 'success', 'amount' => 20 * $rate,
            'currency' => 'NGN', 'domain' => 'test', 'customer' => ['email' => 'owner@example.test'],
        ]]),
    ]);
    app(SubscriptionPayments::class)->checkout($invoice);
    app(SubscriptionPayments::class)->verify(SubscriptionPayment::firstOrFail());
    $this->get('http://portal.tier-signup.edu.ng/login')->assertOk();
    $token = app(TenantContext::class)->run($school, fn () => User::first()->createToken('dashboard-check')->plainTextToken);
    $response = $this->withToken($token)->getJson('http://portal.tier-signup.edu.ng/home')->assertOk()->assertSee('Plans & Renewal');
    if ($tier !== 'platinum') {
        $response->assertDontSee('href="http://portal.tier-signup.edu.ng/admin/tests"', false);
    }
    if ($tier === 'silver') {
        $response->assertDontSee('href="http://portal.tier-signup.edu.ng/academic"', false);
    }
})->with([['silver', 100000], ['gold', 225000], ['platinum', 325000]]);


function operationalSchool($test,string $slug,string $plan='platinum'): array
{
    $provisioner=app(InstitutionProvisioner::class);
    $school=$provisioner->provision($provisioner->register(($test->institutionInput)($slug,$plan)),'Secure-test-password-1');
    return app(TenantContext::class)->run($school,function()use($school){
        $faculty=Faculty::create(['name'=>'Operations faculty','code'=>'OPS']);
        $department=\App\Models\Department::create(['name'=>'Operations department','faculty_id'=>$faculty->id]);
        return [$school,User::first()->id,$faculty->id,$department->id];
    });
}

it('operational integrations encrypt school keys and clear gateway configuration between institutions',function(){
    config(['services.paystack.secret_key'=>'sk_test_legacy_key']);
    [$alpha,$actor]=operationalSchool($this,'ops-alpha');
    [$beta,$betaActor]=operationalSchool($this,'ops-beta');
    $service=app(\App\Services\Tenancy\InstitutionIntegrations::class);
    $service->save($alpha,'sk_test_alpha_key_123',$actor);
    $service->save($beta,'sk_test_beta_key_123',$betaActor);
    $raw=DB::connection('central')->table('institution_integrations')->where('institution_id',$alpha->id)->value('credentials');
    expect($raw)->not->toContain('sk_test_alpha_key_123');
    app(TenantContext::class)->run($alpha,function(){
        expect(config('services.paystack.secret_key'))->toBe('sk_test_alpha_key_123');
        expect(fn()=>app('firebase.auth'))->toThrow(RuntimeException::class);
    });
    app(TenantContext::class)->run($beta,fn()=>expect(config('services.paystack.secret_key'))->toBe('sk_test_beta_key_123'));
    expect(config('services.paystack.secret_key'))->toBe('sk_test_legacy_key')->and(\Illuminate\Support\Facades\Log::sharedContext())->not->toHaveKey('institution_id');
    $service->save($alpha,'sk_test_alpha_rotated_456',$actor);
    app(TenantContext::class)->run($alpha,fn()=>expect($service->signatureValid('body',hash_hmac('sha512','body','sk_test_alpha_key_123')))->toBeTrue());
});

it('operational financial webhooks reject other school signatures and settle suspended school payments once',function(){
    [$alpha,$actor,,$department]=operationalSchool($this,'ops-alpha');
    [$beta,$betaActor,,$betaDepartment]=operationalSchool($this,'ops-beta');
    $integrations=app(\App\Services\Tenancy\InstitutionIntegrations::class);
    $integrations->save($alpha,'sk_test_alpha_key_123',$actor);$integrations->save($beta,'sk_test_beta_key_123',$betaActor);
    $payment=app(TenantContext::class)->run($alpha,function()use($department){
        $payer=User::first();$request=\App\Models\TranscriptRequest::create(['user_id'=>$payer->id,'department_id'=>$department,'purpose'=>'Test transcript','status'=>'awaiting_payment']);
        return app(\App\Services\PaystackPayments::class)->create($request,$payer,'transcript');
    });
    $betaPayment=app(TenantContext::class)->run($beta,function()use($betaDepartment){$payer=User::first();$request=\App\Models\TranscriptRequest::create(['user_id'=>$payer->id,'department_id'=>$betaDepartment,'purpose'=>'Beta transcript','status'=>'awaiting_payment']);return app(\App\Services\PaystackPayments::class)->create($request,$payer,'transcript');});
    Http::preventStrayRequests();Http::fake(['api.paystack.co/*'=>fn($request)=>Http::response(['status'=>true,'data'=>[
        'reference'=>basename($request->url()),'id'=>77,'status'=>'success','amount'=>$payment->amount,'currency'=>'NGN','domain'=>'test','customer'=>['email'=>$payment->email],
    ]])]);
    $body=json_encode(['event'=>'charge.success','data'=>['reference'=>$payment->reference]]);
    $headers=['CONTENT_TYPE'=>'application/json','HTTP_X_PAYSTACK_SIGNATURE'=>hash_hmac('sha512',$body,'sk_test_alpha_key_123')];
    $this->call('POST','http://ops-beta.educore.test/payments/paystack/webhook',[],[],[],$headers,$body)->assertUnauthorized();
    Institution::whereKey($alpha->id)->update(['status'=>'suspended']);
    $this->get('http://ops-alpha.educore.test/login')->assertForbidden();
    $this->call('POST','http://ops-alpha.educore.test/payments/paystack/webhook',[],[],[],$headers,$body)->assertOk();
    $this->call('POST','http://ops-alpha.educore.test/payments/paystack/webhook',[],[],[],$headers,$body)->assertOk();
    expect(DB::connection('central')->table('payments')->where('tenant_id',$alpha->id)->where('id',$payment->id)->value('status'))->toBe('success')
        ->and(DB::connection('central')->table('transcript_requests')->where('tenant_id',$alpha->id)->where('id',$payment->payable_id)->value('status'))->toBe('pending');
    $betaBody=json_encode(['event'=>'charge.success','data'=>['reference'=>$betaPayment->reference]]);
    $this->call('POST','http://ops-beta.educore.test/payments/paystack/webhook',[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_X_PAYSTACK_SIGNATURE'=>hash_hmac('sha512',$betaBody,'sk_test_beta_key_123')],$betaBody)->assertOk();
    expect(DB::connection('central')->table('payments')->where('gateway_id','77')->where('status','success')->count())->toBe(2);
    Http::assertSent(fn($request)=>$request->hasHeader('Authorization','Bearer sk_test_alpha_key_123'));
    expect(app(TenantState::class)->id())->toBeNull()->and(config('tenancy.financial_event',false))->toBeFalse();
});

it('operational central workers preserve identity and discard suspended work without executing it',function(){
    config(['queue.default'=>'database']);[$alpha]=operationalSchool($this,'ops-alpha');[$beta]=operationalSchool($this,'ops-beta');
    foreach([$alpha,$beta]as$school)app(TenantContext::class)->run($school,fn()=>Bus::dispatch(new TenantQueuedUserProbe(User::first())));
    expect(DB::connection('central')->table('jobs')->whereNotNull('tenant_id')->count())->toBe(2);
    $alphaJob=app('queue')->connection('institutions')->pop('institution_'.$alpha->id);app('queue.worker')->process('institutions',$alphaJob,new WorkerOptions);
    Institution::whereKey($beta->id)->update(['status'=>'suspended']);
    $betaJob=app('queue')->connection('institutions')->pop('institution_'.$beta->id);app('queue.worker')->process('institutions',$betaJob,new WorkerOptions);
    expect(DB::connection('central')->table('users')->where('tenant_id',$alpha->id)->value('name'))->toBe('Queued '.$alpha->id)
        ->and(DB::connection('central')->table('users')->where('tenant_id',$beta->id)->value('name'))->toBe('School Administrator')
        ->and(app(TenantState::class)->id())->toBeNull()->and(DB::getDefaultConnection())->toBe('sqlite');
});

it('operational scheduler records unique local slots and processes all school jobs centrally',function(){
    config(['queue.default'=>'database']);[$school]=operationalSchool($this,'ops-alpha');
    $tasks=app(\App\Services\Tenancy\InstitutionTasks::class);$count=$tasks->dispatchDue($school);
    expect($count)->toBeGreaterThanOrEqual(3)->and($tasks->dispatchDue($school))->toBe(0);
    $processed=0;
    while($job=app('queue')->connection('institutions')->pop('institution_'.$school->id)) {app('queue.worker')->process('institutions',$job,new WorkerOptions);if(++$processed>10)throw new RuntimeException('Unexpected scheduled job loop.');}
    expect($processed)->toBe($count)->and(DB::connection('central')->table('institution_task_runs')->where('institution_id',$school->id)->where('status','success')->count())->toBe($count)
        ->and(DB::connection('central')->table('operation_health')->where('tenant_id',$school->id)->where('name','scheduler')->value('status'))->toBe('success');
});

it('operational restores replace only one school and preserve current passwords while revoking its tokens',function(){
    [$alpha,$actor,$alphaFaculty]=operationalSchool($this,'ops-alpha');[$beta,,$betaFaculty]=operationalSchool($this,'ops-beta');
    $context=app(TenantContext::class);foreach([$alpha,$beta]as$school)$context->run($school,function()use($school){Storage::disk('local')->put('same.txt',$school->slug);User::first()->createToken('restore-test');});
    $archives=app(\App\Services\Tenancy\InstitutionArchives::class);$snapshot=$archives->export($alpha,$actor);
    $context->run($alpha,function()use($alphaFaculty){Faculty::findOrFail($alphaFaculty)->update(['name'=>'Changed faculty']);Storage::disk('local')->put('same.txt','changed');User::first()->update(['password'=>'New-current-password-123']);});
    $safety=$archives->restore($alpha,$snapshot,$actor);
    $context->run($alpha,function()use($alphaFaculty){expect(Faculty::findOrFail($alphaFaculty)->name)->toBe('Operations faculty')->and(Storage::disk('local')->get('same.txt'))->toBe('ops-alpha')->and(Hash::check('New-current-password-123',User::first()->password))->toBeTrue()->and(User::first()->tokens()->count())->toBe(0);});
    $context->run($beta,function()use($betaFaculty){expect(Faculty::findOrFail($betaFaculty)->name)->toBe('Operations faculty')->and(Storage::disk('local')->get('same.txt'))->toBe('ops-beta')->and(User::first()->tokens()->count())->toBe(1);});
    expect($safety->institution_id)->toBe($alpha->id)->and(is_file($archives->path($safety)))->toBeTrue();
    expect(fn()=>$archives->restore($beta,$snapshot,$actor))->toThrow(RuntimeException::class);
});

it('operational archives reject tampering and refuse to rewind financial activity',function(){
    [$school,$actor,,$department]=operationalSchool($this,'ops-alpha');$archives=app(\App\Services\Tenancy\InstitutionArchives::class);
    $snapshot=$archives->export($school,$actor);$path=$archives->path($snapshot);$bytes=file_get_contents($path);file_put_contents($path,$bytes.'tamper');
    expect(fn()=>$archives->restore($school,$snapshot,$actor))->toThrow(RuntimeException::class,'checksum');file_put_contents($path,$bytes);
    app(TenantContext::class)->run($school,function()use($department){$payer=User::first();$request=\App\Models\TranscriptRequest::create(['user_id'=>$payer->id,'department_id'=>$department,'purpose'=>'New financial activity','status'=>'awaiting_payment']);app(\App\Services\PaystackPayments::class)->create($request,$payer,'transcript');});
    expect(fn()=>$archives->restore($school,$snapshot,$actor))->toThrow(RuntimeException::class,'Financial records changed');
    expect(DB::connection('central')->table('payments')->where('tenant_id',$school->id)->count())->toBe(1);
});

it('operational lifecycle changes audit archival and domain changes without deleting school records',function(){
    [$alpha,$actor]=operationalSchool($this,'ops-alpha');[$beta]=operationalSchool($this,'ops-beta');
    $lifecycle=app(\App\Services\Tenancy\InstitutionLifecycle::class);
    $lifecycle->status($alpha,'archived','Institution archived for retention',1);
    expect(DB::connection('central')->table('users')->where('tenant_id',$alpha->id)->count())->toBe(1);
    $this->get('http://ops-alpha.educore.test/login')->assertForbidden();
    expect(fn()=>$lifecycle->domain($alpha,'ops-beta.educore.test','Attempt duplicate institution domain',1))->toThrow(ValidationException::class);
    config(['tenancy.central_domains'=>['educore.test','billing.educore.test']]);
    expect(fn()=>$lifecycle->domain($alpha,'billing.educore.test','Reject reserved central hostname',1))->toThrow(ValidationException::class);
    $lifecycle->domain($alpha,'ops-renamed.educore.test','Institution portal renamed by owner',1);
    $this->get('http://ops-alpha.educore.test/login')->assertNotFound();
    $lifecycle->status($alpha->fresh(),'ready','Institution reactivated after review',1);
    $this->get('http://ops-renamed.educore.test/login')->assertOk();
    expect(DB::connection('central')->table('institution_events')->where('institution_id',$alpha->id)->count())->toBe(3);
});

it('operational failed upgrades retain maintenance and require complete schema recovery',function(){
    [$school]=operationalSchool($this,'ops-alpha');
    $manager=Mockery::mock(\App\Services\Backups\DatabaseBackups::class);$manager->shouldReceive('create')->once()->andThrow(new RuntimeException('Simulated operator backup failure'));
    app()->instance(\App\Services\Backups\DatabaseBackups::class,$manager);
    $this->artisan('tenancy:upgrade',['--writers-paused'=>true])->assertFailed();
    expect(app(\App\Services\Tenancy\PlatformMaintenance::class)->active())->toBeTrue();
    $this->get('http://ops-alpha.educore.test/login')->assertStatus(503);
    $originalMigrations=config('tenancy.central_migrations');
    File::ensureDirectoryExists($this->tenantTestRoot.'/pending');
    File::put($this->tenantTestRoot.'/pending/2026_10_18_999999_pending_operation.php','<?php');
    config(['tenancy.central_migrations'=>$this->tenantTestRoot.'/pending']);
    $this->artisan('tenancy:maintenance',['mode'=>'off','--reason'=>'Pending migration must retain maintenance'])->assertFailed();
    expect(app(\App\Services\Tenancy\PlatformMaintenance::class)->active())->toBeTrue();
    config(['tenancy.central_migrations'=>$originalMigrations]);
    $this->artisan('tenancy:maintenance',['mode'=>'off','--reason'=>'Verified shared schema after failed backup'])->assertSuccessful();
    $this->get('http://ops-alpha.educore.test/login')->assertOk();
});


it('operational recovery rolls back interrupted file activation before making a school available',function(){
    [$school,$actor]=operationalSchool($this,'ops-alpha');
    app(TenantContext::class)->run($school,fn()=>Storage::disk('local')->put('record.txt','original files'));
    $archives=app(\App\Services\Tenancy\InstitutionArchives::class);$snapshot=$archives->export($school,$actor);
    $id=(string)Str::uuid();$root=config('tenancy.storage_root').'/'.$school->id;$stage=$root.'/.restore-'.$id;
    File::ensureDirectoryExists($stage.'/new-public');File::put($stage.'/journal.json',json_encode(['had'=>['private'=>true,'public'=>false]]));
    rename($root.'/private',$stage.'/old-private');File::ensureDirectoryExists($root.'/private');File::put($root.'/private/record.txt','uncommitted restored files');
    DB::connection('central')->table('institution_restore_runs')->insert(['id'=>$id,'institution_id'=>$school->id,'export_id'=>$snapshot->id,'safety_export_id'=>$snapshot->id,'previous_status'=>'ready','status'=>'prepared','created_at'=>now()]);
    Institution::whereKey($school->id)->update(['status'=>'restoring']);$this->get('http://ops-alpha.educore.test/login')->assertForbidden();
    $this->artisan('tenancy:recover-restore',['institution'=>'ops-alpha','--reason'=>'Recovered simulated interrupted file activation'])->assertSuccessful();
    $this->get('http://ops-alpha.educore.test/login')->assertOk();
    expect(File::get($root.'/private/record.txt'))->toBe('original files')->and(DB::connection('central')->table('institution_restore_runs')->where('id',$id)->value('status'))->toBe('rolled_back');
});


function operationalBrowserSession($test,$school,int $actor): void {
    config(['session.driver'=>'database']);
    $response=$test->post('http://'.$school->domains()->first()->domain.'/login',['email'=>'admin@example.test','password'=>'Secure-test-password-1'])->assertRedirect();
    $cookie=$response->getCookie('institution_'.$school->id.'_session');
    if(!$cookie)throw new RuntimeException('Institution session cookie was not issued.');
    $test->withCookie('institution_'.$school->id.'_session',$cookie->getValue());
}

it('operational integration forms never reveal or flash school secrets',function(){
    [$school,$actor]=operationalSchool($this,'ops-alpha');operationalBrowserSession($this,$school,$actor);
    $this->get('http://ops-alpha.educore.test/institution/integrations')->assertOk()->assertSee('Not configured');
    $this->put('http://ops-alpha.educore.test/institution/integrations',['paystack_secret_key'=>'sk_test_alpha_private_key','current_password'=>'wrong'])->assertRedirect();
    expect(base64_decode(DB::connection('central')->table('institution_sessions')->where('tenant_id',$school->id)->latest('last_activity')->value('payload')))->not->toContain('sk_test_alpha_private_key');
    $this->put('http://ops-alpha.educore.test/institution/integrations',['paystack_secret_key'=>'sk_test_alpha_private_key','current_password'=>'Secure-test-password-1'])->assertRedirect();
    $this->get('http://ops-alpha.educore.test/institution/integrations')->assertOk()->assertDontSee('sk_test_alpha_private_key');
    expect(app(\App\Services\Tenancy\InstitutionIntegrations::class)->key($school->id))->toBe('sk_test_alpha_private_key');
});

it('operational school export endpoints reject another institutions archive identifiers',function(){
    [$alpha,$actor]=operationalSchool($this,'ops-alpha');[$beta,$betaActor]=operationalSchool($this,'ops-beta');
    $archives=app(\App\Services\Tenancy\InstitutionArchives::class);$other=$archives->export($beta,$betaActor);
    operationalBrowserSession($this,$alpha,$actor);
    $this->get('http://ops-alpha.educore.test/institution/exports')->assertOk();
    $this->get('http://ops-alpha.educore.test/institution/exports/'.$other->id.'/download')->assertNotFound();
    $this->post('http://ops-alpha.educore.test/institution/exports/'.$other->id.'/restore',['current_password'=>'Secure-test-password-1','confirmation'=>'RESTORE '.$other->id,'acknowledge'=>1])->assertNotFound();
    $this->post('http://ops-alpha.educore.test/institution/exports',['current_password'=>'Secure-test-password-1'])->assertRedirect();
    $own=\App\Models\Central\InstitutionExport::where('institution_id',$alpha->id)->firstOrFail();
    $this->get('http://ops-alpha.educore.test/institution/exports/'.$own->id.'/download')->assertDownload();
});


it('launch catalogue excludes deferred products from new invoices and describes the available scope',function(){
    Plan::query()->update(['signup_enabled'=>true]);
    $this->get('http://educore.test/pricing')->assertOk()->assertSee('Deferred — excluded from this release')->assertSee('Course materials library')->assertDontSee('plus staff, assignments, wallet');
    $this->get('http://educore.test/subscribe/gold')->assertOk()->assertSee('Deferred features are excluded from this release');
    $this->post('http://educore.test/subscribe/gold',institutionSignupInput('launch-school'))->assertRedirect();
    $invoice=SubscriptionInvoice::firstOrFail();$codes=collect($invoice->feature_snapshot)->pluck('code');
    expect($codes)->toContain('result_management')->not->toContain('wallet','mobile_application','assignment_management','staff_management');
    $school=Institution::firstOrFail();$school->forceFill(['subscription_status'=>'not_required'])->save();
    app(TenantContext::class)->run($school,function(){foreach(['wallet','mobile_application','assignment_management','staff_management','mailing','dynamic_timetable']as$feature)expect(app(\App\Services\Tenancy\FeaturePermissions::class)->allows($feature))->toBeFalse();});
});

it('launch importer remaps collisions foreign links saved answers and passwords without charging or changing another school',function(){
    [$other]=operationalSchool($this,'other-school');
    $file=$this->tenantTestRoot.'/source.sqlite';touch($file);
    config(['database.connections.legacy_snapshot'=>[...config('database.connections.central'),'database'=>$file]]);
    expect(Artisan::call('migrate',['--database'=>'legacy_snapshot','--force'=>true]))->toBe(0);
    $source=DB::connection('legacy_snapshot');$hash=Hash::make('Legacy-password-123');
    $source->table('faculties')->insert(['id'=>1,'name'=>'Legacy faculty','code'=>'LEG']);
    $source->table('departments')->insert(['id'=>1,'name'=>'Legacy department','faculty_id'=>1]);
    $source->table('users')->insert(['id'=>1,'name'=>'Legacy owner','email'=>'legacy@example.test','password'=>$hash,'usertype'=>'admin','department_id'=>1]);
    $source->table('teams')->insert(['id'=>1,'name'=>'Legacy team','user_id'=>1,'personal_team'=>true]);$source->table('users')->where('id',1)->update(['current_team_id'=>1]);
    $source->table('tests')->insert(['id'=>1,'name'=>'Legacy CBT','subject'=>'History','duration'=>10,'status'=>'active','level'=>'100','department_id'=>1]);
    $source->table('questions')->insert(['id'=>1,'tests_id'=>1,'question_text'=>'Question','options'=>json_encode(['A','B']),'correct_option'=>0,'marks'=>1]);
    $source->table('responses')->insert(['id'=>1,'test_id'=>1,'student_id'=>1,'answers'=>json_encode((object)[1=>0]),'score'=>1]);
    $source->table('results')->insert(['id'=>1,'user_id'=>1,'department_id'=>1,'matric_number'=>'LEG/1','session'=>'2026/2027','semester'=>'First','level'=>'100','course_code'=>'HIS101','course_title'=>'History','credit_unit'=>3,'score'=>50,'grade'=>'C','grade_point'=>3,'full_transcript_path'=>'https://legacy.example.test/storage/documents/transcripts/old.pdf']);
    $provisioner=app(InstitutionProvisioner::class);$school=$provisioner->register(['slug'=>'legacy-school','name'=>'Legacy school','domain'=>'legacy-school.educore.test','owner_name'=>'Legacy owner','owner_email'=>'legacy@example.test','plan_code'=>'silver','timezone'=>'Africa/Lagos']);
    $files=$this->tenantTestRoot.'/source-files';File::ensureDirectoryExists($files.'/private');File::ensureDirectoryExists($files.'/public');File::put($files.'/private/document.pdf','legacy document');File::ensureDirectoryExists($files.'/private/documents/transcripts');File::put($files.'/private/documents/transcripts/old.pdf','legacy evidence');
    $source->table('users')->where('id',1)->update(['profile_photo_path'=>'profile-photos/missing.jpg']);
    $documents=app(\App\Services\Tenancy\LegacyDocumentAudit::class)->inspect('legacy_snapshot',$files);
    expect($documents['ready'])->toBeFalse()->and(count($documents['missing_references']))->toBe(1);
    $source->table('users')->where('id',1)->update(['profile_photo_path'=>null]);
    expect(app(\App\Services\Tenancy\LegacyDocumentAudit::class)->inspect('legacy_snapshot',$files)['ready'])->toBeTrue();
    $importer=app(\App\Services\Tenancy\LegacyInstitutionImport::class);
    expect(app(\App\Services\Tenancy\LegacyMigrationPreflight::class)->inspect('legacy_snapshot')['ready'])->toBeTrue();
    $prepared=$importer->prepare('legacy_snapshot');
    $source->table('users')->where('id',1)->update(['profile_photo_path'=>'profile-photos/missing.jpg']);
    expect(fn()=>$importer->import($school,$prepared,$files))->toThrow(RuntimeException::class,'Recover the originals');
    expect($school->fresh()->status)->toBe('pending');
    $source->table('users')->where('id',1)->update(['profile_photo_path'=>null]);
    $report=$importer->import($school,$prepared,$files);
    expect(count($report['unverified_legacy_document_links']))->toBe(1);
    expect(DB::connection('central')->table('results')->where('tenant_id',$school->id)->value('full_transcript_path'))->toBeNull();
    $newId=$report['maps']['users'][1];expect($newId)->not->toBe(1)->and($school->fresh()->status)->toBe('imported')->and(SubscriptionInvoice::count())->toBe(0);
    $this->get('http://legacy-school.educore.test/login')->assertForbidden();
    $terms=['plan_code'=>'silver','semester_name'=>'Agreed migration semester','semester_start'=>'2026-10-01','semester_end'=>'2027-01-31','student_count'=>1,'reason'=>'Owner approved no-charge migration semester','agreed_no_charge'=>true];
    $invoice=app(\App\Services\Tenancy\ImportedInstitutionActivation::class)->activate($school,$terms);expect($invoice->amount_minor)->toBe(0)->and(SubscriptionPayment::count())->toBe(0);
    app(TenantContext::class)->run($school->fresh(),function()use($newId,$hash){$user=User::findOrFail($newId);expect($user->password)->toBe($hash)->and($user->currentTeam->user_id)->toBe($newId)->and($user->department->name)->toBe('Legacy department')->and(Storage::disk('local')->get('document.pdf'))->toBe('legacy document');});
    app(TenantContext::class)->run($other,fn()=>expect(User::first()->name)->toBe('School Administrator'));
    expect($source->table('users')->where('id',1)->value('password'))->toBe($hash);
    expect(fn()=>$importer->import($school->fresh(),$prepared,$files))->toThrow(RuntimeException::class);
    DB::purge('legacy_snapshot');
});

it('launch preflight blocks dangling live relationships and activation needs explicit agreement',function(){
    [$school]=operationalSchool($this,'agreement-school');
    $terms=['plan_code'=>'silver','semester_name'=>'First semester','semester_start'=>'2026-10-01','semester_end'=>'2027-01-31','student_count'=>1,'reason'=>'No subscription terms agreed yet','agreed_no_charge'=>false];
    expect(fn()=>app(\App\Services\Tenancy\ImportedInstitutionActivation::class)->activate($school,$terms))->toThrow(ValidationException::class);
    expect(fn()=>app(\App\Services\Tenancy\LegacyInstitutionImport::class)->prepare(DB::getDefaultConnection()))->toThrow(RuntimeException::class,'restored source');
    expect(SubscriptionInvoice::count())->toBe(0);
});


it('launch capability API binds bearer authentication and feature permissions to the same institution',function(){
    [$silver]=operationalSchool($this,'api-silver','silver');[$platinum]=operationalSchool($this,'api-platinum','platinum');
    $tokens=[];foreach([$silver,$platinum]as$school)$tokens[$school->id]=app(TenantContext::class)->run($school,fn()=>User::first()->createToken('client-qa')->plainTextToken);
    $this->getJson('http://api-silver.educore.test/api/institution/features')->assertUnauthorized();
    $this->withToken($tokens[$silver->id])->getJson('http://api-silver.educore.test/api/institution/features')->assertOk()->assertJsonPath('features.portal_control',true)->assertJsonPath('features.result_management',false)->assertJsonPath('features.cbt',false)->assertJsonPath('features.mobile_application',false);
    $this->withToken($tokens[$silver->id])->getJson('http://api-platinum.educore.test/api/institution/features')->assertUnauthorized();
    $this->withToken($tokens[$platinum->id])->getJson('http://api-platinum.educore.test/api/institution/features')->assertOk()->assertJsonPath('features.cbt',true)->assertJsonPath('features.result_management',true)->assertJsonPath('features.wallet',false)->assertJsonPath('features.mailing',false);
});
