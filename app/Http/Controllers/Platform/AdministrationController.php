<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\PlatformAdministrator;
use App\Services\Tenancy\PlatformAudit;
use App\Services\Tenancy\PlatformSecurity as Security;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdministrationController extends Controller
{
    public function index()
    {
        return view('platform.administrators', ['administrators' => PlatformAdministrator::orderBy('name')->paginate(25)]);
    }

    public function invite(Request $request)
    {
        Security::password($request);
        $request->merge(['email' => strtolower(trim($request->input('email', '')))]);
        $data = $request->validate(['name' => 'required|string|max:255', 'email' => ['required', 'email', 'max:255', Rule::unique(config('tenancy.central_connection').'.platform_administrators')],
            'role' => ['required', Rule::in(array_keys(PlatformAdministrator::ROLES))]]);
        Security::db()->transaction(function () use ($data) {
            $admin = new PlatformAdministrator(['name' => $data['name'], 'email' => $data['email'], 'password' => Str::random(64)]);
            $admin->role = $data['role'];
            $admin->save();
            AccountController::sendToken($admin, 'invite');
            PlatformAudit::record('administrator.created', (string) $admin->id, ['name' => $admin->name, 'email' => $admin->email, 'role' => $admin->role]);
        });

        return back()->with('status', 'Administrator invited. They must set a password using the emailed link.');
    }

    public function update(Request $request, int $administrator)
    {
        Security::password($request);
        $data = $request->validate(['role' => ['required', Rule::in(array_keys(PlatformAdministrator::ROLES))], 'is_active' => 'required|boolean', 'reason' => 'required|string|min:10|max:2000']);
        Security::db()->transaction(function () use ($administrator, $data) {
            // Serialize owner changes, including concurrent attempts against different owners.
            $admins = PlatformAdministrator::orderBy('id')->lockForUpdate()->get();
            $target = $admins->firstWhere('id', $administrator);
            abort_unless($target, 404);
            $actor = $admins->firstWhere('id', Auth::guard('platform')->id());
            abort_unless($actor?->is_active && $actor->role === 'owner', 403);
            if ($target->is_active && $target->role === 'owner' && (! $data['is_active'] || $data['role'] !== 'owner')
                && $admins->where('role', 'owner')->where('is_active', true)->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'The last active owner cannot be deactivated or demoted.']);
            }
            $before = ['role' => $target->role, 'is_active' => $target->is_active];
            $after = ['role' => $data['role'], 'is_active' => (bool) $data['is_active']];
            if ($before === $after) {
                return;
            }
            $target->role = $after['role'];
            $target->is_active = $after['is_active'];
            $target->save();
            Security::revoke($target);
            Security::db()->table('platform_account_tokens')->where('email', $target->email)->delete();
            PlatformAudit::record('administrator.updated', (string) $target->id, ['before' => $before, 'after' => $after, 'reason' => $data['reason']]);
        });

        return back()->with('status', 'Administrator updated. Changed accounts must sign in again.');
    }

    public function resend(Request $request, int $administrator)
    {
        Security::password($request);
        $admin = PlatformAdministrator::where('is_active', true)->findOrFail($administrator);
        AccountController::sendToken($admin, 'reset');

        return back()->with('status', 'A password setup/recovery link was sent. Two-factor authentication is preserved.');
    }

    public function audit(Request $request)
    {
        $filters = $request->validate(['event' => 'nullable|string|max:100', 'actor' => 'nullable|integer|min:1', 'subject' => 'nullable|string|max:255',
            'from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);
        $query = Security::db()->table('platform_audit_events')->leftJoin('platform_administrators', 'actor_id', '=', 'platform_administrators.id')
            ->select('platform_audit_events.*', 'platform_administrators.name as actor_name')->orderByDesc('platform_audit_events.id');
        foreach (['event' => 'event', 'actor' => 'actor_id', 'subject' => 'subject'] as $input => $column) {
            if (! empty($filters[$input])) {
                $query->where('platform_audit_events.'.$column, $filters[$input]);
            }
        }
        if (! empty($filters['from'])) {
            $query->where('platform_audit_events.created_at', '>=', $filters['from'].' 00:00:00');
        }
        if (! empty($filters['to'])) {
            $query->where('platform_audit_events.created_at', '<=', $filters['to'].' 23:59:59');
        }

        return view('platform.audit', ['events' => $query->paginate(30)->withQueryString(),
            'actors' => PlatformAdministrator::orderBy('name')->get(),
            'eventNames' => Security::db()->table('platform_audit_events')->distinct()->orderBy('event')->pluck('event')]);
    }
}
