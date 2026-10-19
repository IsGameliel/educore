<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Jobs\SynchronizeInstitutionDatabase;
use App\Models\Central\Institution;
use App\Models\Central\InstitutionDatabaseCopy;
use App\Services\Tenancy\InstitutionDatabaseCopies;
use App\Services\Tenancy\PlatformAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InstitutionDatabaseCopyController extends Controller
{
    public function save(Request $request, string $institution, InstitutionDatabaseCopies $service)
    {
        Institution::whereKey($institution)->lockForUpdate()->firstOrFail();
        $copy = InstitutionDatabaseCopy::whereKey($institution)->lockForUpdate()->first();
        abort_if($copy?->direction === 'inbound', 409, 'An active primary connection can only be changed by a supervised operator procedure.');
        $data = $request->validate(['database_name' => ['required', 'string', 'max:64',
            Rule::unique(config('tenancy.central_connection').'.institution_database_copies', 'database_name')->ignore($institution, 'institution_id')],
            'username' => ['required', 'string', 'max:80', Rule::unique(config('tenancy.central_connection').'.institution_database_copies', 'username')->ignore($institution, 'institution_id')], 'password' => [$copy ? 'nullable' : 'required', 'string', 'max:255'],
            'reason' => 'required|string|min:10|max:2000', 'confirm_copy' => 'accepted']);
        try {
            $service->validateTarget($data['database_name'], $data['username']);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['database_name' => $exception->getMessage()]);
        }
        if ($copy && ($copy->database_name !== $data['database_name'] || $copy->username !== $data['username']) && ! $request->filled('password')) {
            throw ValidationException::withMessages(['password' => 'Enter the password for the new database connection.']);
        }
        $copy ??= new InstitutionDatabaseCopy(['institution_id' => $institution]);
        $copy->fill(['database_name' => $data['database_name'], 'username' => $data['username'],
            'enabled' => true, 'configuration_version' => (string) Str::uuid(), 'queued_at' => null, 'status' => 'configured',
            'last_synced_at' => null, 'manifest' => null, 'row_count' => 0, 'failure_code' => null]);
        if ($request->filled('password')) {
            // An owner can replace damaged ciphertext without first decrypting it.
            $copy->setRawAttributes([...$copy->getAttributes(), 'password' => null]);
            $copy->syncOriginalAttribute('password');
            $copy->password = $data['password'];
        }
        $copy->save();
        PlatformAudit::record('institution_copy.configured', $institution, ['database_name' => $copy->database_name, 'reason' => $data['reason']]);

        return redirect()->route('platform.institutions.show', $institution)->with('status', 'Database copy configured. The scheduler will queue its first verified snapshot.');
    }

    public function sync(Request $request, string $institution)
    {
        $copy = InstitutionDatabaseCopy::where('enabled', true)->lockForUpdate()->findOrFail($institution);
        if (($copy->status === 'running' && $copy->last_attempt_at?->gt(now()->subMinutes(10))) || ($copy->status === 'queued' && $copy->queued_at?->gt(now()->subMinutes(10)))) {
            return back()->with('status', 'A synchronization is already queued or running.');
        }
        $copy->forceFill(['status' => 'queued', 'queued_at' => now()])->save();
        SynchronizeInstitutionDatabase::dispatch($institution);

        return redirect()->route('platform.institutions.show', $institution)->with('status', 'Database synchronization queued.');
    }

    public function resume(Request $request, string $institution)
    {
        $data = $request->validate(['reason' => 'required|string|min:10|max:2000']);
        $copy = InstitutionDatabaseCopy::whereKey($institution)->where('direction', 'inbound')->lockForUpdate()->firstOrFail();
        $copy->forceFill(['enabled' => true, 'status' => 'configured', 'queued_at' => null, 'configuration_version' => (string) Str::uuid()])->save();
        PlatformAudit::record('institution_copy.resumed', $institution, ['reason' => $data['reason']]);

        return redirect()->route('platform.institutions.show', $institution)->with('status', 'Central synchronization resumed.');
    }

    public function pause(Request $request, string $institution)
    {
        $data = $request->validate(['reason' => 'required|string|min:10|max:2000']);
        $copy = InstitutionDatabaseCopy::whereKey($institution)->lockForUpdate()->firstOrFail();
        $copy->forceFill(['enabled' => false, 'status' => 'paused', 'configuration_version' => (string) Str::uuid()])->save();
        PlatformAudit::record('institution_copy.paused', $institution, ['reason' => $data['reason']]);

        return redirect()->route('platform.institutions.show', $institution)->with('status', 'Synchronization paused. Existing copy and shared records are retained.');
    }
}
