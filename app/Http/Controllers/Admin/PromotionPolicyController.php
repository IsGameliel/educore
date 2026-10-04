<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromotionPolicy;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionPolicyController extends Controller
{
    public function edit()
    {
        return view('admin.promotion-policy', ['policy' => PromotionPolicy::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['max_carryovers' => 'required|integer|min:0|max:1000']);
        DB::transaction(function () use ($request, $data) {
            $policy = PromotionPolicy::whereKey(1)->lockForUpdate()->firstOrFail();
            $previous = $policy->max_carryovers;
            $policy->update($data + ['updated_by' => $request->user()->id]);
            ActivityLogger::log($request->user(), 'promotion_policy_updated', 'Updated maximum carryovers for promotion.', [
                'subject' => $policy, 'properties' => ['previous_max_carryovers' => $previous, 'max_carryovers' => $policy->max_carryovers],
            ]);
        });
        return redirect()->route('admin.promotion-policy.edit')->with('success', 'Promotion policy saved successfully.');
    }
}
