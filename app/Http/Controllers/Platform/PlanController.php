<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\Feature;
use App\Models\Central\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index()
    {
        return view('platform.plans', ['plans' => Plan::withCount('features')->orderBy('standard_rate_minor')->get()]);
    }

    public function create()
    {
        return view('platform.plan', ['plan' => new Plan(['promotion_semesters' => 1]), 'features' => Feature::orderBy('name')->get()]);
    }

    public function edit(string $plan)
    {
        return view('platform.plan', ['plan' => Plan::with('features')->findOrFail($plan), 'features' => Feature::orderBy('name')->get()]);
    }

    public function save(Request $request, ?string $plan = null)
    {
        $existing = $plan ? Plan::findOrFail($plan) : null;
        $data = $request->validate([
            'code' => $existing ? ['prohibited'] : ['required', 'regex:/\A[a-z][a-z0-9_-]{0,31}\z/D', Rule::notIn(['login', 'logout', 'callback', 'checkout', 'refresh', 'provision', 'webhook', 'renew', 'onboarding', 'portal']), Rule::unique(config('tenancy.central_connection').'.plans', 'code')],
            'name' => 'required|string|max:255',
            'standard_price' => ['required', 'numeric', 'min:0.01', 'max:999999', 'regex:/\A\d+(?:\.\d{1,2})?\z/D'],
            'promotional_price' => ['required', 'numeric', 'min:0.01', 'lte:standard_price', 'regex:/\A\d+(?:\.\d{1,2})?\z/D'],
            'signup_enabled' => 'sometimes|boolean',
            'features' => 'required|array|min:1',
            'features.*' => ['required', 'string', 'distinct', Rule::exists(config('tenancy.central_connection').'.features', 'code')],
        ]);
        $minor = static function (string $price): int {
            [$whole, $fraction] = array_pad(explode('.', $price, 2), 2, '');

            return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
        };
        DB::connection(config('tenancy.central_connection'))->transaction(function () use ($existing, $data, $request, $minor) {
            $plan = $existing ?? new Plan(['code' => $data['code']]);
            $plan->fill(['name' => $data['name'], 'currency' => 'NGN', 'billing_unit' => 'student_per_semester',
                'standard_rate_minor' => $minor($data['standard_price']), 'promotional_rate_minor' => $minor($data['promotional_price']),
                'promotion_semesters' => 1, 'signup_enabled' => $request->boolean('signup_enabled')])->save();
            $plan->features()->sync($data['features']);
        });

        return redirect()->route('platform.plans.index')->with('status', 'Plan saved. Existing invoices keep their original prices and features.');
    }
}
