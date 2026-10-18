<?php

namespace App\Http\Controllers;

use App\Models\Central\Feature;
use App\Services\Tenancy\FeaturePermissions;

class InstitutionCapabilitiesController extends Controller
{
    public function __invoke(FeaturePermissions $permissions)
    {
        return response()->json(['features' => Feature::orderBy('code')->get()->mapWithKeys(fn ($feature) => [$feature->code => $permissions->allows($feature->code)])])->header('Cache-Control', 'private, no-store');
    }
}
