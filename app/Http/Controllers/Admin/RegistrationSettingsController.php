<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistrationSetting;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistrationSettingsController extends Controller
{
    public function edit()
    {
        return view('admin.registration-settings', ['settings' => RegistrationSetting::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'registration_open' => 'required|boolean',
            'require_fee_clearance' => 'required|boolean',
            'require_late_registration_fee' => 'sometimes|boolean',
            'application_fee_enabled' => 'sometimes|boolean',
        ]);
        DB::transaction(function () use ($request, $data) {
            $current = RegistrationSetting::current();
            $settings = RegistrationSetting::query()->lockForUpdate()->findOrFail($current->id);
            $tracked = ['registration_open', 'require_fee_clearance', 'require_late_registration_fee', 'application_fee_enabled'];
            $before = $settings->only($tracked);
            $settings->update($data + ['updated_by' => $request->user()->id]);
            ActivityLogger::log($request->user(), 'registration_settings_updated', 'Updated applicant fee and student course registration settings.', [
                'subject' => $settings,
                'properties' => ['before' => $before, 'after' => $settings->only($tracked)],
            ]);
        });
        return redirect()->route('admin.registration-settings.edit')->with('success', 'Registration settings saved successfully.');
    }
}
