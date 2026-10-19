<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\Institution;
use App\Models\Central\InstitutionAccount;
use App\Models\Central\InstitutionDomain;
use App\Models\Central\Plan;
use App\Models\Central\SubscriptionInvoice;
use App\Models\Central\SubscriptionPayment;
use App\Services\Tenancy\InstitutionDomains;
use App\Services\Tenancy\InstitutionOnboarding;
use App\Services\Tenancy\InstitutionProvisioner;
use App\Services\Tenancy\SemesterBilling;
use App\Services\Tenancy\SubscriptionPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SignupController extends Controller
{
    public function pricing()
    {
        return view('pricing', ['plans' => Plan::with('features')->orderBy('standard_rate_minor')->get()]);
    }

    public function create(string $plan)
    {
        return view('platform.signup', ['plan' => Plan::with('features')->where('signup_enabled', true)->findOrFail($plan)]);
    }

    public function store(Request $request, string $plan, InstitutionProvisioner $provisioner)
    {
        $request->validate([
            'domain' => 'required|string|max:253',
            'name' => 'required|string|max:255',
            'slug' => ['required', 'max:63', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', Rule::unique(config('tenancy.central_connection').'.institutions', 'slug'), Rule::notIn(['www', 'mail', 'admin', 'platform', 'api'])],
            'owner_name' => 'required|string|max:255', 'owner_email' => 'required|email|max:255',
            'password' => 'required|string|min:12|max:255|confirmed',
            'student_count' => 'required|integer|min:1|max:100000',
            'semester_name' => 'required|string|max:255', 'semester_start' => 'required|date_format:Y-m-d',
            'semester_end' => 'required|date_format:Y-m-d|after:semester_start',
            'confirm_census' => 'accepted',
        ]);
        $domain = InstitutionDomains::normalize($request->input('domain'));
        $base = strtolower(config('tenancy.institution_domain'));
        if (str_ends_with($domain, '.'.$base)) {
            throw ValidationException::withMessages(['domain' => 'Use an address on your institution\'s own domain, such as portal.rivers-college.gov.ng.']);
        }
        if (InstitutionDomain::where('domain', $domain)->exists()) {
            throw ValidationException::withMessages(['domain' => 'This portal address is already assigned to an institution.']);
        }
        $institution = DB::connection(config('tenancy.central_connection'))->transaction(function () use ($request, $plan, $provisioner, $domain) {
            $plan = Plan::with('features')->where('signup_enabled', true)->lockForUpdate()->findOrFail($plan);
            $institution = $provisioner->register([
                ...$request->only(['name', 'slug', 'owner_name', 'owner_email']),
                'domain' => $domain,
                'plan_code' => $plan->code, 'timezone' => 'Africa/Lagos',
            ]);
            $institution->forceFill(['subscription_status' => 'awaiting_payment', 'settings' => ['domain_pending_verification' => true, 'dedicated_database_required' => (bool) config('tenancy.directadmin.enabled')]])->save();
            // Commit central records before the tenant connection writes school records.
            InstitutionAccount::create(['institution_id' => $institution->id, 'name' => $request->owner_name,
                'email' => $institution->owner_email, 'password' => $request->password]);
            app(SemesterBilling::class)->issue($institution, $plan, $request->only(['semester_name', 'semester_start', 'semester_end', 'student_count']));

            return $institution;
        });
        Auth::guard('institution_account')->login(InstitutionAccount::where('institution_id', $institution->id)->firstOrFail());
        $request->session()->regenerate();
        try {
            $provisioner->provision($institution, $request->password);
        } catch (\Throwable $exception) {
            app('redirect')->setSession($request->session());

            return redirect()->route('subscription.show')->withErrors(['provisioning' => 'Your account and invoice are saved. School setup needs another attempt before payment.']);
        }
        app('redirect')->setSession($request->session());

        return redirect()->route('subscription.show');
    }

    public function login()
    {
        return view('platform.institution-login');
    }

    public function authenticate(Request $request)
    {
        $data = $request->validate(['slug' => 'required|string|max:63', 'email' => 'required|email', 'password' => 'required|string']);
        $institution = Institution::where('slug', strtolower($data['slug']))->first();
        if (! $institution || ! Auth::guard('institution_account')->attempt([
            'institution_id' => $institution->id, 'email' => strtolower(trim($data['email'])), 'password' => $data['password'],
        ])) {
            return back()->withErrors(['email' => 'These institution account credentials are invalid.'])->onlyInput('slug', 'email');
        }
        $request->session()->regenerate();

        return redirect()->route('subscription.show');
    }

    private function institution(): Institution
    {
        abort_unless(Auth::guard('institution_account')->check(), 403);

        return Institution::with('domains')->findOrFail(Auth::guard('institution_account')->user()->institution_id);
    }

    public function show()
    {
        if (! Auth::guard('institution_account')->check()) {
            return redirect()->route('subscription.login');
        }
        $institution = $this->institution();

        $onboarding = app(InstitutionOnboarding::class);
        if ($onboarding->available($institution) && $onboarding->required($institution)) {
            return redirect()->route('subscription.onboarding');
        }

        return view('platform.subscription', ['institution' => $institution,
            'invoice' => SubscriptionInvoice::where('institution_id', $institution->id)->orderByDesc('semester_start')->firstOrFail(),
            'invoices' => SubscriptionInvoice::where('institution_id', $institution->id)->orderByDesc('semester_start')->get(),
            'plans' => Plan::with('features')->where('signup_enabled', true)->orderBy('standard_rate_minor')->get(),
            'portalAvailable' => app(InstitutionOnboarding::class)->available($institution)]);
    }

    public function renew(Request $request, SemesterBilling $billing)
    {
        $institution = $this->institution();
        abort_unless($institution->status === 'ready', 403, 'School setup must be complete and suspension lifted before renewal.');
        $data = $request->validate([
            'plan_code' => ['required', 'string', Rule::exists(config('tenancy.central_connection').'.plans', 'code')],
            'semester_name' => 'required|string|max:255',
            'semester_start' => 'required|date_format:Y-m-d',
            'semester_end' => 'required|date_format:Y-m-d|after:semester_start',
            'student_count' => 'required|integer|min:1|max:100000',
            'confirm_census' => 'accepted',
        ]);
        $billing->issue($institution, Plan::findOrFail($data['plan_code']), $data);

        return redirect()->route('subscription.show')->with('status', 'Your next-semester invoice is ready. Your current semester access is unchanged.');
    }

    public function checkout(SubscriptionPayments $payments)
    {
        $institution = $this->institution();
        $invoice = SubscriptionInvoice::where('institution_id', $institution->id)->orderByDesc('semester_start')->firstOrFail();
        try {
            return redirect()->away($payments->checkout($invoice));
        } catch (RuntimeException $exception) {
            return redirect()->route('subscription.show')->withErrors(['payment' => $exception->getMessage()]);
        }
    }

    public function provision(Request $request, InstitutionProvisioner $provisioner)
    {
        $institution = $this->institution();
        $data = $request->validate(['password' => 'required|string']);
        abort_unless(Hash::check($data['password'], Auth::guard('institution_account')->user()->password), 403, 'The institution account password is invalid.');
        try {
            $provisioner->provision($institution, $data['password']);
        } catch (\Throwable $exception) {
            app('redirect')->setSession($request->session());

            return back()->withErrors(['provisioning' => 'School setup could not finish. Please retry or contact the platform administrator.']);
        }
        app('redirect')->setSession($request->session());

        return back()->with('status', 'School setup completed. You can now proceed with payment.');
    }

    public function callback(Request $request, SubscriptionPayments $payments)
    {
        $request->validate(['reference' => 'required|string|max:255']);
        $institution = $this->institution();
        $invoiceIds = SubscriptionInvoice::where('institution_id', $institution->id)->pluck('id');
        $payment = SubscriptionPayment::whereIn('invoice_id', $invoiceIds)->where('reference', $request->reference)->firstOrFail();
        try {
            $payments->verify($payment);
        } catch (\Throwable $exception) {
            return redirect()->route('subscription.show')->withErrors(['payment' => 'Payment could not be confirmed. Use Check payment status to retry.']);
        }

        return $this->afterPayment($institution);
    }

    public function refresh(SubscriptionPayments $payments)
    {
        $institution = $this->institution();
        $invoice = SubscriptionInvoice::where('institution_id', $institution->id)->orderByDesc('semester_start')->firstOrFail();
        $payment = SubscriptionPayment::where('invoice_id', $invoice->id)->latest()->first();
        if (! $payment) {
            return back()->withErrors(['payment' => 'Start payment checkout first.']);
        }
        try {
            $payments->verify($payment);
        } catch (\Throwable $exception) {
            return back()->withErrors(['payment' => 'Payment has not been confirmed. Please try again shortly.']);
        }

        return $this->afterPayment($institution);
    }

    private function afterPayment(Institution $institution)
    {
        $institution->refresh();
        if (app(InstitutionOnboarding::class)->available($institution)) {
            return redirect()->route('subscription.onboarding');
        }

        return redirect()->route('subscription.show')->with('status', 'Payment confirmed. Portal access requires an active institution and an unexpired subscription.');
    }

    public function webhook(Request $request, SubscriptionPayments $payments)
    {
        $key = config('tenancy.paystack_secret_key');
        abort_unless(is_string($key) && $key !== '' && hash_equals(hash_hmac('sha512', $request->getContent(), $key), (string) $request->header('x-paystack-signature')), 401);
        if ($request->input('event') === 'charge.success') {
            $payment = SubscriptionPayment::where('reference', $request->input('data.reference'))->first();
            if ($payment) {
                try {
                    $payments->verify($payment);
                } catch (\Throwable $exception) {
                    return response()->json(['message' => 'Verification pending'], 503);
                }
            }
        }

        return response()->json(['received' => true]);
    }

    public function logout(Request $request)
    {
        Auth::guard('institution_account')->logout();
        $request->session()->regenerate(true);

        return redirect()->route('subscription.login');
    }
}
