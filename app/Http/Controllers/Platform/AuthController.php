<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\Feature;
use App\Models\Central\Institution;
use App\Models\Central\PlatformAdministrator;
use App\Models\Central\SubscriptionInvoice;
use App\Models\Central\SubscriptionPayment;
use App\Services\Tenancy\InstitutionDatabases;
use App\Services\Tenancy\InstitutionLifecycle;
use App\Services\Tenancy\PlatformAudit;
use App\Services\Tenancy\PlatformSecurity;
use App\Services\Tenancy\SemesterBilling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login()
    {
        return view('platform.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $admin = PlatformAdministrator::where('email', strtolower($credentials['email']))->where('is_active', true)->first();
        if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            PlatformAudit::record('security.login_failed', null);

            return back()->withErrors(['email' => 'These platform credentials are invalid.'])->onlyInput('email');
        }
        if ($admin->two_factor_confirmed_at) {
            Auth::guard('platform')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put(['platform_pending_admin' => $admin->id, 'platform_pending_at' => now()->timestamp, 'platform_pending_version' => $admin->security_version]);

            return redirect()->route('platform.challenge.form');
        }
        PlatformSecurity::login($request, $admin);

        return redirect()->route('platform.index');
    }

    public function index(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:255', 'status' => 'nullable|in:pending,provisioning,ready,failed,suspended,archived',
            'subscription' => 'nullable|in:awaiting_payment,active,not_required']);
        $institutions = Institution::with('domains')->latest();
        if (! empty($filters['search'])) {
            $institutions->where(fn ($query) => $query->where('name', 'like', '%'.$filters['search'].'%')->orWhere('slug', 'like', '%'.$filters['search'].'%'));
        }
        if (! empty($filters['status'])) {
            $institutions->where('status', $filters['status']);
        }
        if (! empty($filters['subscription'])) {
            $institutions->where('subscription_status', $filters['subscription']);
        }

        return view('platform.index', ['institutions' => $institutions->paginate(20)->withQueryString(),
            'metrics' => ['total' => Institution::count(), 'new' => Institution::where('created_at', '>=', now()->subDays(7))->count(),
                'awaiting' => Institution::where('subscription_status', 'awaiting_payment')->count(),
                'failed' => Institution::where('status', 'failed')->count(),
                'collected' => SubscriptionInvoice::where('status', 'paid')->sum('amount_minor')]]);
    }

    public function show(string $institution)
    {
        $institution = Institution::with('domains')->findOrFail($institution);
        $central = DB::connection(config('tenancy.central_connection'));
        $statistics = app(InstitutionDatabases::class)->statistics($institution->id);
        $invoices = SubscriptionInvoice::where('institution_id', $institution->id)->latest()->get();

        return view('platform.institution', ['institution' => $institution, 'invoices' => $invoices,
            'payments' => SubscriptionPayment::whereIn('invoice_id', $invoices->pluck('id'))->latest()->get(),
            'students' => $statistics['students'],
            'staff' => $statistics['staff'],
            'features' => Feature::where('implementation_status', '!=', 'not_found')->orderBy('name')->get(),
            'overrides' => $central->table('institution_feature_overrides')->where('institution_id', $institution->id)->orderByDesc('id')->get(),
            'events' => $central->table('institution_events')->where('institution_id', $institution->id)->orderByDesc('id')->limit(30)->get(),
            'taskRuns' => $central->table('institution_task_runs')->where('institution_id', $institution->id)->orderByDesc('id')->limit(30)->get(),
            'operations' => app(InstitutionDatabases::class)->operations($institution->id),
            'attempts' => $central->table('provisioning_attempts')->where('institution_id', $institution->id)->orderByDesc('started_at')->limit(10)->get()]);
    }

    public function overrideFeature(Request $request, string $institution)
    {
        $institution = Institution::findOrFail($institution);
        $data = $request->validate([
            'feature_code' => ['required', Rule::exists(config('tenancy.central_connection').'.features', 'code')],
            'allowed' => 'required|boolean', 'reason' => 'required|string|min:10|max:2000',
            'expires_at' => 'required|date|after:now|before_or_equal:'.now()->addDays(90)->toDateTimeString(),
        ]);
        DB::connection(config('tenancy.central_connection'))->transaction(function () use ($institution, $data) {
            DB::connection(config('tenancy.central_connection'))->table('institution_feature_overrides')->insert([
                ...$data, 'institution_id' => $institution->id, 'administrator_id' => Auth::guard('platform')->id(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            app(SemesterBilling::class)->record($institution->id, 'feature_override', [
                ...$data, 'administrator_id' => Auth::guard('platform')->id(),
            ]);
        });

        return back()->with('status', 'Temporary feature override recorded. This does not bypass payment or institution suspension.');
    }

    public function status(Request $request, string $institution)
    {
        $data = $request->validate(['status' => 'required|in:ready,suspended,archived', 'reason' => 'required|string|min:10|max:2000']);
        app(InstitutionLifecycle::class)->status(Institution::findOrFail($institution), $data['status'], $data['reason'], Auth::guard('platform')->id());

        return back()->with('status', 'Institution lifecycle status updated and audited.');
    }

    public function domain(Request $request, string $institution)
    {
        $data = $request->validate(['domain' => 'required|string|max:253', 'reason' => 'required|string|min:10|max:2000', 'dns_ready' => 'accepted']);
        app(InstitutionLifecycle::class)->domain(Institution::findOrFail($institution), $data['domain'], $data['reason'], Auth::guard('platform')->id());

        return back()->with('status', 'Primary institution domain changed. Existing sessions and tokens were revoked.');
    }

    public function logout(Request $request)
    {
        PlatformAudit::record('security.logout', (string) Auth::guard('platform')->id());
        PlatformSecurity::db()->table('platform_login_sessions')->where('id', $request->session()->get('platform_login_key'))->delete();
        Auth::guard('platform')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform.login');
    }
}
