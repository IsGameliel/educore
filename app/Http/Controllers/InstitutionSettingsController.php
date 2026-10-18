<?php

namespace App\Http\Controllers;

use App\Models\Central\Institution;
use App\Services\Tenancy\TenantState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InstitutionSettingsController extends Controller
{
    private function institution(): Institution
    {
        return Institution::findOrFail(app(TenantState::class)->id());
    }

    public function edit()
    {
        return view('institution.settings', ['institution' => $this->institution()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'timezone' => 'required|timezone',
            'contact_email' => 'nullable|email|max:255', 'contact_phone' => 'nullable|string|max:50',
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'], 'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048']);
        $institution = $this->institution();
        $settings = [...($institution->settings ?? []), ...collect($data)->only(['contact_email', 'contact_phone', 'primary_color'])->all()];
        $newLogo = $request->hasFile('logo') ? $request->file('logo')->store('branding', 'public') : null;
        if ($newLogo) {
            $settings['logo_path'] = $newLogo;
        }
        try {
            DB::connection(config('tenancy.central_connection'))->transaction(function () use ($institution, $data, $settings) {
                $institution->forceFill(['name' => $data['name'], 'timezone' => $data['timezone'], 'settings' => $settings])->save();
                DB::connection(config('tenancy.central_connection'))->table('institution_profiles')->where('tenant_id', $institution->id)
                    ->update(['name' => $data['name'], 'timezone' => $data['timezone'], 'updated_at' => now()]);
            });
        } catch (\Throwable $exception) {
            if ($newLogo) {
                Storage::disk('public')->delete($newLogo);
            } throw $exception;
        }

        return back()->with('success', 'Institution settings saved.');
    }

    public function logo()
    {
        $path = $this->institution()->settings['logo_path'] ?? null;
        abort_unless($path && str_starts_with($path, 'branding/') && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function asset(string $path)
    {
        abort_if(str_contains($path, '..') || str_contains($path, '\\') || str_starts_with($path, '/'), 404);
        if (str_starts_with($path, 'course_materials/') || str_starts_with($path, 'course_material_covers/')) {
            app(\App\Services\Tenancy\FeaturePermissions::class)->authorize('e_learning');
        }
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
