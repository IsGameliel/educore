<?php
// php tests/Manual/TenancyAcceptance.php
// Fresh disposable MySQL database; simulated gateway only; never restores live data.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Central\{Institution, Plan, SubscriptionInvoice, SubscriptionPayment};
use App\Models\{Department, Faculty, User};
use App\Services\Tenancy\{FeaturePermissions, InstitutionProvisioner, SemesterBilling, SubscriptionPayments, TenantContext, TenantState};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{Artisan, Cache, DB, File, Hash, Http, Storage};
use Illuminate\Support\Str;
function qaCheck(bool $passed, string $label): void {
    if (! $passed) { throw new RuntimeException('FAILED: '.$label); }
    echo 'PASS: '.$label.PHP_EOL;
}
if (! $app->environment('local', 'testing')) { throw new RuntimeException('Local/test environments only.'); }
$originalDefault = DB::getDefaultConnection();
$centralName = config('tenancy.central_connection');
$centralConfig = config('database.connections.'.$centralName);
if (($centralConfig['driver'] ?? '') !== 'mysql') { throw new RuntimeException('This rehearsal requires MySQL.'); }
$qaDatabase = 'educore_qa_'.date('Ymd').'_'.bin2hex(random_bytes(6));
if (! preg_match('/\Aeducore_qa_[0-9]{8}_[a-f0-9]{12}\z/D', $qaDatabase)) { throw new RuntimeException('Unsafe QA database name.'); }
$qaRoot = storage_path('framework/testing/'.$qaDatabase);
$created = false;
$exitCode = 0;
try {
    config(['database.connections.qa_control' => [...$centralConfig, 'database' => null, 'url' => null]]);
    $control = DB::connection('qa_control');
    qaCheck(! $control->table('information_schema.SCHEMATA')->where('SCHEMA_NAME', $qaDatabase)->exists(), 'Disposable database name is unused');
    $control->statement('CREATE DATABASE '.$qaDatabase.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    File::ensureDirectoryExists($qaRoot);
    config([
        'database.connections.'.$centralName => [...$centralConfig, 'database' => $qaDatabase, 'url' => null],
        'database.connections.tenant' => [...$centralConfig, 'database' => '__tenant_context_required__', 'url' => null],
        'tenancy.storage_root' => $qaRoot.'/files', 'tenancy.lock_directory' => $qaRoot.'/locks',
        'tenancy.exports.directory' => $qaRoot.'/exports', 'tenancy.maintenance_file' => $qaRoot.'/maintenance.json',
        'tenancy.paystack_secret_key' => 'sk_test_qa_fixture', 'cache.default' => 'array', 'queue.default' => 'sync', 'mail.default' => 'array',
    ]);
    DB::purge($centralName);
    DB::purge('tenant');
    qaCheck(Artisan::call('tenancy:install', ['--no-interaction' => true]) === 0, 'Fresh central and tenant migrations on MySQL');
    Plan::query()->update(['signup_enabled' => true]);
    $provisioner = app(InstitutionProvisioner::class);
    $context = app(TenantContext::class);
    $schools = [];
    $faculties = [];
    $password = Str::random(40);
    foreach (['silver', 'gold', 'platinum'] as $tier) {
        $school = $provisioner->register(['slug' => 'qa-'.$tier, 'name' => 'QA '.ucfirst($tier), 'domain' => 'qa-'.$tier.'.educore.test',
            'owner_name' => 'QA Administrator', 'owner_email' => 'same-admin@example.test', 'plan_code' => $tier, 'timezone' => 'Africa/Lagos']);
        $schools[$tier] = $provisioner->provision($school, $password);
        $faculties[$tier] = $context->run($school, function () use ($tier) {
            $faculty = Faculty::create(['name' => 'QA Faculty', 'code' => 'SAME']);
            Storage::disk('local')->put('same.txt', $tier);
            Cache::put('same-key', $tier);
            $student = User::first();
            $student->matric_number = 'QA/001';
            $student->setRelation('department', new Department(['name' => 'QA Department']));
            $idCard = view('student.id-card.card', ['student' => $student, 'photo' => null, 'logo' => '', 'stamp' => '',
                'expiresAt' => now()->addYear(), 'expired' => false, 'missing' => ['photo']])->render();
            qaCheck(str_contains($idCard, config('app.name')) && ! str_contains($idCard, 'MUDIAME UNIVERSITY'), ucfirst($tier).' ID card branding');
            $mail = new \App\Mail\StudentAccountCreated($student, 'synthetic-qa-password');
            $emailHtml = $mail->render();
            qaCheck(str_contains($emailHtml, config('app.name')) && str_contains($emailHtml, route('login')) && parse_url(route('login'), PHP_URL_HOST) === 'qa-'.$tier.'.educore.test', ucfirst($tier).' account email branding and school link');
            qaCheck(User::count() === 1 && User::first()->ownedTeams()->count() === 1, ucfirst($tier).' isolated administrator/team');
            return $faculty;
        });
        $provisioner->provision($school->fresh(), Str::random(40));
        $context->run($school, fn () => qaCheck(Hash::check($password, User::first()->password), ucfirst($tier).' retry preserves administrator password'));
    }
    qaCheck(Institution::count() === 3, 'Three institutions share one disposable database');
    foreach ($schools as $tier => $school) {
        $context->run($school, function () use ($tier, $faculties) {
            qaCheck(Faculty::count() === 1 && DB::table('faculties')->where('name', 'missing')->orWhere('code', 'SAME')->count() === 1, ucfirst($tier).' raw OR query isolation');
            qaCheck(Storage::disk('local')->get('same.txt') === $tier && Cache::get('same-key') === $tier, ucfirst($tier).' file/cache isolation');
            if ($tier === 'gold') {
                qaCheck(Faculty::withoutGlobalScopes()->find($faculties['silver']->id) === null, 'Cross-school record lookup denied');
                $rejected = false;
                try { Department::create(['name' => 'Cross-school', 'faculty_id' => $faculties['silver']->id]); }
                catch (\Illuminate\Validation\ValidationException $e) { $rejected = true; }
                qaCheck($rejected, 'Cross-school foreign key write rejected');
            }
        });
    }
    $integrations = app(\App\Services\Tenancy\InstitutionIntegrations::class);
    foreach (['silver','gold'] as $tier) {
        $integrations->save($schools[$tier], 'sk_test_qa_'.$tier.'_123', 1);
        $context->run($schools[$tier], fn () => qaCheck(config('services.paystack.secret_key') === 'sk_test_qa_'.$tier.'_123', ucfirst($tier).' institution payment credentials'));
    }
    qaCheck(! str_contains(DB::connection($centralName)->table('institution_integrations')->value('credentials'), 'sk_test_'), 'Payment credentials encrypted at rest');
    $archives = app(\App\Services\Tenancy\InstitutionArchives::class);
    $actor = $context->run($schools['silver'], fn () => User::first()->id);
    $snapshot = $archives->export($schools['silver'], $actor);
    $context->run($schools['silver'], function () { Faculty::first()->update(['name'=>'Changed after export']); Storage::disk('local')->put('same.txt','changed'); });
    $safety = $archives->restore($schools['silver'], $snapshot, $actor);
    $context->run($schools['silver'], fn () => qaCheck(Faculty::first()->name === 'QA Faculty' && Storage::disk('local')->get('same.txt') === 'silver', 'MySQL scoped restore restores own rows and files'));
    $context->run($schools['gold'], fn () => qaCheck(Faculty::first()->name === 'QA Faculty' && Storage::disk('local')->get('same.txt') === 'gold', 'MySQL scoped restore leaves other school untouched'));
    qaCheck(is_file($archives->path($safety)), 'MySQL restore creates safety archive');
    config(['queue.default'=>'database']);
    $tasks = app(\App\Services\Tenancy\InstitutionTasks::class);
    $dispatched = $tasks->dispatchDue($schools['platinum']);
    qaCheck($dispatched > 0 && $tasks->dispatchDue($schools['platinum']) === 0, 'MySQL scheduler claims slots idempotently');
    $processed = 0;
    while ($job = app('queue')->connection('institutions')->pop('institution_'.$schools['platinum']->id)) {
        app('queue.worker')->process('institutions', $job, new \Illuminate\Queue\WorkerOptions);
        if (++$processed > 10) throw new RuntimeException('Unexpected QA job loop');
    }
    qaCheck(DB::connection($centralName)->table('institution_task_runs')->where('institution_id',$schools['platinum']->id)->where('status','success')->count() === $dispatched, 'MySQL central worker completes tenant tasks');
    config(['queue.default'=>'sync']);
    $lifecycle = app(\App\Services\Tenancy\InstitutionLifecycle::class);
    $lifecycle->status($schools['gold'],'archived','QA archive retention verification',1);
    qaCheck(DB::connection($centralName)->table('users')->where('tenant_id',$schools['gold']->id)->count()===1, 'Archival retains institution records');
    $lifecycle->status($schools['gold']->fresh(),'ready','QA reactivation verification',1);
    $lifecycle->domain($schools['gold']->fresh(),'qa-gold-renamed.educore.test','QA institution domain verification',1);
    qaCheck($schools['gold']->domains()->where('domain','qa-gold-renamed.educore.test')->exists(), 'Audited domain replacement');
    $lifecycle->domain($schools['gold']->fresh(),'qa-gold.educore.test','QA institution domain restored',1);
    $billing = app(SemesterBilling::class);
    $payments = app(SubscriptionPayments::class);
    Http::preventStrayRequests();
    Http::fake([
        'api.paystack.co/transaction/initialize' => fn ($request) => Http::response(['status' => true, 'data' => ['reference' => $request['reference'], 'authorization_url' => 'https://checkout.paystack.com/qa-fixture']]),
        'api.paystack.co/transaction/verify/*' => function ($request) {
            $payment = SubscriptionPayment::where('reference', basename($request->url()))->firstOrFail();
            $invoice = SubscriptionInvoice::findOrFail($payment->invoice_id);
            return Http::response(['status' => true, 'data' => ['reference' => $payment->reference, 'id' => hexdec(substr(hash('sha256', $payment->reference), 0, 12)),
                'status' => 'success', 'amount' => $invoice->amount_minor, 'currency' => 'NGN', 'domain' => 'test', 'customer' => ['email' => $payment->email]]]);
        },
    ]);
    $firstDate = Carbon::today('Africa/Lagos');
    $endDate = $firstDate->copy()->addMonths(4);
    foreach (['silver' => 100000, 'gold' => 225000, 'platinum' => 325000] as $tier => $rate) {
        $school = $schools[$tier];
        $school->forceFill(['subscription_status' => 'awaiting_payment'])->save();
        $input = ['semester_name' => 'QA First', 'semester_start' => $firstDate->toDateString(), 'semester_end' => $endDate->toDateString(), 'student_count' => 20];
        $invoice = $billing->issue($school, Plan::findOrFail($tier), $input);
        qaCheck(! $billing->accessible($school), ucfirst($tier).' unpaid subscription denied');
        qaCheck($invoice->rate_minor === $rate && $invoice->amount_minor === 20 * $rate, ucfirst($tier).' first-semester pricing');
        qaCheck($billing->issue($school, Plan::findOrFail($tier), $input)->id === $invoice->id, ucfirst($tier).' identical invoice retry is idempotent');
        qaCheck($payments->checkout($invoice) === $payments->checkout($invoice), ucfirst($tier).' checkout retry reuses pending transaction');
        $payment = SubscriptionPayment::where('invoice_id', $invoice->id)->firstOrFail();
        $payments->verify($payment);
        $payments->verify($payment->fresh());
        qaCheck($billing->accessible($school->fresh()), ucfirst($tier).' simulated verified payment activates access');
        $context->run($school, function () use ($tier) {
            $permissions = app(FeaturePermissions::class);
            qaCheck($permissions->allows('result_management') === ($tier !== 'silver') && $permissions->allows('cbt') === ($tier === 'platinum'), ucfirst($tier).' package permissions');
        });
    }
    qaCheck(DB::connection($centralName)->table('subscription_events')->where('event', 'payment_verified')->count() === 3, 'Duplicate verification does not duplicate payment audit effects');
    $apiToken = $context->run($schools['silver'], fn () => User::first()->createToken('mysql-qa')->plainTextToken);
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    foreach (['silver' => 200, 'gold' => 401] as $tier => $expectedStatus) {
        $request = \Illuminate\Http\Request::create('http://qa-'.$tier.'.educore.test/api/user', 'GET', [], [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$apiToken, 'HTTP_ACCEPT' => 'application/json']);
        $response = $kernel->handle($request);
        qaCheck($response->getStatusCode() === $expectedStatus, ucfirst($tier).' bearer token ownership');
        $kernel->terminate($request, $response);
    }
    $school = $schools['silver']->fresh();
    $nextStart = $endDate->copy()->addDay();
    $nextEnd = $nextStart->copy()->addMonths(4);
    $renewal = $billing->issue($school, Plan::findOrFail('gold'), ['semester_name' => 'QA Second', 'semester_start' => $nextStart->toDateString(), 'semester_end' => $nextEnd->toDateString(), 'student_count' => 30]);
    qaCheck(! $renewal->promotional && $renewal->amount_minor === 30 * 450000, 'Renewal uses next plan standard rate');
    $payments->checkout($renewal);
    $payments->verify(SubscriptionPayment::where('invoice_id', $renewal->id)->firstOrFail());
    qaCheck($billing->current($school)->plan_code === 'silver', 'Paying ahead retains current package');
    Carbon::setTestNow($nextStart->copy()->addHours(12));
    qaCheck($billing->current($school)->plan_code === 'gold', 'Paid renewal activates at semester start');
    Carbon::setTestNow($nextEnd->copy()->addDay()->addHours(12));
    qaCheck(! $billing->accessible($school), 'Portal access expires after paid semester');
    qaCheck(app(TenantState::class)->id() === null && DB::getDefaultConnection() === $originalDefault, 'Context cleared after all institutions');
    echo 'MySQL acceptance rehearsal passed.'.PHP_EOL;
} catch (Throwable $e) {
    echo 'Acceptance failure: '.$e::class.': '.$e->getMessage().PHP_EOL;
    $exitCode = 1;
} finally {
    Carbon::setTestNow();
    DB::purge('tenant');
    DB::purge($centralName);
    config(['database.connections.'.$centralName => $centralConfig]);
    DB::setDefaultConnection($originalDefault);
    if ($created) {
        // Drop only the exact database this run created after the unused-name check.
        DB::connection('qa_control')->statement('DROP DATABASE '.$qaDatabase);
        echo 'Disposable database removed.'.PHP_EOL;
    }
    DB::purge('qa_control');
    if (is_dir($qaRoot)) {
        $resolved = realpath($qaRoot);
        $testingRoot = realpath(storage_path('framework/testing'));
        if (! $resolved || ! $testingRoot || ! str_starts_with($resolved, $testingRoot.DIRECTORY_SEPARATOR) || basename($resolved) !== $qaDatabase) {
            throw new RuntimeException('Refusing cleanup outside the exact QA directory.');
        }
        File::deleteDirectory($resolved);
        echo 'Disposable storage removed.'.PHP_EOL;
    }
}
exit($exitCode);
