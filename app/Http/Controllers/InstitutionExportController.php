<?php

namespace App\Http\Controllers;

use App\Models\Central\Institution;
use App\Models\Central\InstitutionExport;
use App\Services\Tenancy\InstitutionArchives;
use App\Services\Tenancy\TenantState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class InstitutionExportController extends Controller
{
    private function school(): Institution
    {
        return Institution::findOrFail(app(TenantState::class)->id());
    }

    private function archive(string $id): InstitutionExport
    {
        return InstitutionExport::where('institution_id', app(TenantState::class)->id())->findOrFail($id);
    }

    public function index()
    {
        return view('institution.exports', ['exports' => InstitutionExport::where('institution_id', $this->school()->id)->latest()->limit(30)->get()]);
    }

    private function password(Request $request): void
    {
        $request->validate(['current_password' => 'required|string']);
        abort_unless(Hash::check($request->current_password, $request->user()->password), 403, 'Your administrator password is incorrect.');
    }

    public function store(Request $request, InstitutionArchives $archives)
    {
        $this->password($request);
        try {
            $archives->export($this->school(), $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['export' => $e->getMessage()]);
        }

        return back()->with('success', 'Institution export created. It contains only your school records and files.');
    }

    public function download(string $export, InstitutionArchives $archives)
    {
        $record = $this->archive($export);
        abort_unless($record->status === 'ready' && $record->expires_at->isFuture(), 404);
        $path = $archives->path($record);
        abort_unless(is_file($path) && ! is_link($path) && hash_equals($record->checksum, hash_file('sha256', $path)), 404);

        return response()->download($path, 'institution-export-'.$record->id.'.zip', ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function restore(Request $request, string $export, InstitutionArchives $archives)
    {
        $record = $this->archive($export);
        $this->password($request);
        $request->validate(['confirmation' => ['required', Rule::in(['RESTORE '.$record->id])], 'acknowledge' => 'accepted']);
        try {
            $archives->restore($this->school(), $record, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['restore' => $e->getMessage()]);
        }
        Auth::guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Institution restore completed. Sign in again. A safety export was retained.');
    }

    public function destroy(Request $request, string $export, InstitutionArchives $archives)
    {
        $record = $this->archive($export);
        $this->password($request);
        $request->validate(['confirmation' => ['required', Rule::in(['DELETE '.$record->id])]]);
        $archives->remove($record, $request->user()->id);

        return back()->with('success','Export archive removed; school records were preserved.');
    }
}
