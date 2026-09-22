<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Backups\DatabaseBackups;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class BackupController extends Controller
{
    public function index(DatabaseBackups $backups)
    {
        return view('admin.backups.index', ['backups' => $backups->listing()]);
    }

    public function store(Request $request, DatabaseBackups $backups)
    {
        try {
            $backup = $backups->create();
        } catch (Throwable $exception) {
            Log::warning('Dashboard backup failed', ['actor_id' => $request->user()->id, 'type' => $exception::class]);
            throw ValidationException::withMessages(['backup' => 'Backup failed. Check database health, server permissions and MySQL tools. No restore was performed.']);
        }
        Log::notice('Dashboard backup created', ['actor_id' => $request->user()->id, 'backup' => $backup['name']]);

        return back()->with('success', 'Backup created: '.$backup['name']);
    }

    public function download(string $backup, DatabaseBackups $backups)
    {
        $file = $backups->find($backup);

        return response()->download($file['path'], $file['name'], ['Cache-Control' => 'private, no-store']);
    }

    public function confirm(string $backup, DatabaseBackups $backups)
    {
        $file = $backups->find($backup);

        return view('admin.backups.restore', ['backup' => $file, 'inspection' => $backups->inspect($file)]);
    }

    public function restore(Request $request, string $backup, DatabaseBackups $backups)
    {
        $file = $backups->find($backup);
        $request->validate([
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', Rule::in(['RESTORE '.$file['name']])],
            'understand' => ['accepted'],
            'checksum' => ['required', 'regex:/^[a-f0-9]{64}$/'],
        ]);
        // StartSession saves this same store after the response: never let it write
        // into a database whose sessions table is being replaced.
        $request->session()->setHandler(new ArraySessionHandler(config('session.lifetime')));
        $request->session()->invalidate();
        try {
            $backups->restore($backup, $request->string('checksum')->toString(), $request->user()->id);
        } catch (Throwable $exception) {
            Log::warning('Dashboard restore interrupted', ['actor_id' => $request->user()->id, 'type' => $exception::class]);
            $message = $exception instanceof \RuntimeException ? $exception->getMessage() : 'Restore failed. Contact the server administrator and check maintenance status before continuing.';

            return response()->view('admin.backups.restore-failed', ['message' => $message], 503);
        }
        Auth::guard('web')->logoutCurrentDevice();
        Auth::forgetGuards();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->view('admin.backups.restore-complete');
    }
}
