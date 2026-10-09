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
        ]);
        DB::transaction(function () use ($request, $data) {
            $settings = RegistrationSetting::whereKey(1)->lockForUpdate()->firstOrFail();
            $before = $settings->only(['registration_open', 'require_fee_clearance', 'require_late_registration_fee']);
            $settings->update($data + ['updated_by' => $request->user()->id]);
            ActivityLogger::log($request->user(), 'registration_settings_updated', 'Updated student course registration controls.', [
                'subject' => $settings,
                'properties' => ['before' => $before, 'after' => $settings->only(['registration_open', 'require_fee_clearance', 'require_late_registration_fee'])],
            ]);
        });
        return redirect()->route('admin.registration-settings.edit')->with('success', 'Course registration settings saved successfully.');
    }
}
