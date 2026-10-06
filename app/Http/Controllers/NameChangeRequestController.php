<?php

namespace App\Http\Controllers;

use App\Models\{NameChangeRequest, User};
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\ValidationException;

class NameChangeRequestController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->user()->dashboardRole() === 'admin';
        abort_unless($admin || $request->user()->dashboardRole() === 'student', 403);
        $requests = NameChangeRequest::with('user')->when(! $admin, fn ($q) => $q->where('user_id', $request->user()->id))->latest()->paginate(20);
        return view('profile.name-change-requests', compact('requests', 'admin'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->dashboardRole() === 'student', 403);
        $data = $request->validate([
            'requested_name' => 'required|string|max:255',
            'reason' => 'required|string|min:10|max:2000',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        $path = $request->file('document')->store('name-change-documents', 'local');
        try {
            DB::transaction(function () use ($request, $data, $path) {
                $user = User::lockForUpdate()->findOrFail($request->user()->id);
                if (trim($data['requested_name']) === $user->name) {
                    throw ValidationException::withMessages(['requested_name' => 'Enter the corrected name.']);
                }
                if (NameChangeRequest::where('user_id', $user->id)->where('status', 'pending')->exists()) {
                    throw ValidationException::withMessages(['requested_name' => 'You already have a pending name-change request.']);
                }
                NameChangeRequest::create(['user_id' => $user->id, 'original_name' => $user->name,
                    'requested_name' => trim($data['requested_name']), 'reason' => $data['reason'], 'document_path' => $path]);
            });
        } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
        return back()->with('success', 'Name-change request submitted for admin review.');
    }

    public function document(Request $request, NameChangeRequest $nameChange)
    {
        abort_unless($request->user()->id === $nameChange->user_id || $request->user()->dashboardRole() === 'admin', 403);
        abort_unless(Storage::disk('local')->exists($nameChange->document_path), 404);
        return Storage::disk('local')->download($nameChange->document_path);
    }

    public function review(Request $request, NameChangeRequest $nameChange)
    {
        abort_unless($request->user()->dashboardRole() === 'admin', 403);
        $data = $request->validate(['decision' => 'required|in:approved,rejected', 'review_note' => 'required|string|min:5|max:2000']);
        DB::transaction(function () use ($request, $nameChange, $data) {
            $user = User::lockForUpdate()->findOrFail($nameChange->user_id);
            $change = NameChangeRequest::lockForUpdate()->findOrFail($nameChange->id);
            abort_unless($change->status === 'pending', 409);
            if ($data['decision'] === 'approved') {
                if ($user->name !== $change->original_name) {
                    throw ValidationException::withMessages(['decision' => 'The student name has changed since submission. Review the current record before rejecting or requesting a new submission.']);
                }
                $user->update(['name' => $change->requested_name]);
            }
            $change->update(['review_note' => $data['review_note'], 'status' => $data['decision'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
            ActivityLogger::log($request->user(), 'name_change_reviewed', 'Reviewed student name-change request.', ['subject' => $change, 'target_user' => $user, 'properties' => ['decision' => $data['decision'], 'original_name' => $change->original_name, 'requested_name' => $change->requested_name]]);
        });
        return back()->with('success', 'Name-change request '.$data['decision'].'.');
    }
}
