<?php

namespace App\Http\Controllers;

use App\Models\Central\Institution;
use App\Services\Tenancy\InstitutionIntegrations;
use App\Services\Tenancy\TenantState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class InstitutionIntegrationController extends Controller
{
    public function edit(InstitutionIntegrations $integrations)
    {
        $key = $integrations->key();

        return view('institution.integrations', ['configured' => (bool) $key, 'mode' => $key ? (str_starts_with($key, 'sk_live_') ? 'live' : 'test') : null]);
    }

    public function update(Request $request, InstitutionIntegrations $integrations)
    {
        // Never flash integration secrets into session old-input or return them to the browser.
        $validator = Validator::make($request->only(['paystack_secret_key', 'current_password']), [
            'paystack_secret_key' => ['required', 'string', 'regex:/\Ask_(test|live)_[A-Za-z0-9_\-]{6,200}\z/D'], 'current_password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator);
        }
        if (! Hash::check($request->current_password, $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Your administrator password is incorrect.']);
        }
        $integrations->save(Institution::findOrFail(app(TenantState::class)->id()), $request->paystack_secret_key, $request->user()->id);

        return back()->with('success', 'Institution payment configuration saved.');
    }
}
