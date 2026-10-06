<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class StudentIdCardController extends Controller
{
    private function data(Request $request, bool $issue = false): array
    {
        $student = User::findOrFail($request->user()->id);
        $student->loadMissing('department');
        $photo = null;
        $path = (string) $student->profile_photo_path;
        // Read only a stored profile image, never a URL or arbitrary server file.
        if ($path !== '' && ! str_contains($path, '..') && ! str_starts_with($path, '/') && ! str_contains($path, '\\')) {
            $disk = Storage::disk(config('jetstream.profile_photo_disk', 'public'));
            if ($disk->exists($path) && $disk->size($path) <= 5 * 1024 * 1024) {
                $bytes = $disk->get($path);
                $type = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
                if (in_array($type, ['image/jpeg', 'image/png']) && @getimagesizefromstring($bytes)) {
                    $photo = 'data:'.$type.';base64,'.base64_encode($bytes);
                }
            }
        }
        $missing = [];
        if (! trim((string) $student->name)) { $missing[] = 'student name'; }
        if (! trim((string) $student->matric_number)) { $missing[] = 'matric number'; }
        if (! $photo) { $missing[] = 'profile photo (JPG or PNG)'; }
        if (! $student->department) { $missing[] = 'department'; }
        $validityYears = ['100' => 6, '200' => 4, '300' => 3, '400' => 2, '500' => 1, '600' => 1][(string) $student->level] ?? null;
        if (! $validityYears) { $missing[] = 'valid student level'; }
        if ($issue && ! count($missing)) {
            // Lock first issuance so simultaneous PDF and image requests share one expiry.
            $student = DB::transaction(function () use ($student, $validityYears) {
                $locked = User::whereKey($student->id)->lockForUpdate()->firstOrFail();
                if (! $locked->temporary_id_expires_at) {
                    $issued = $locked->temporary_id_issued_at ?? today();
                    $locked->forceFill([
                        'temporary_id_issued_at' => $issued,
                        'temporary_id_expires_at' => $issued->copy()->addYearsNoOverflow($validityYears),
                    ])->save();
                }
                return $locked->load('department');
            });
        }
        $expiresAt = $student->temporary_id_expires_at ?? ($validityYears ? today()->addYearsNoOverflow($validityYears) : null);
        $expired = $expiresAt && $expiresAt->lt(today());

        return compact('student', 'photo', 'missing', 'expiresAt', 'expired') + [
            'logo' => 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('asset/images/euvion.png'))),
            'stamp' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents(public_path('asset/images/student-id-verified.svg'))),
            'filename' => 'temporary-student-id-'.$student->id,
        ];
    }

    public function index(Request $request)
    {
        return response()->view('student.id-card.index', $this->data($request))->header('Cache-Control', 'private, no-store');
    }

    public function image(Request $request)
    {
        $data = $this->data($request, true);
        abort_if(count($data['missing']) > 0, 422, 'Complete your name, matric number, department, level and profile photo before downloading your ID card.');
        abort_if($data['expired'], 422, 'Your temporary ID card has expired. Contact student administration.');

        return response()->view('student.id-card.card', $data)
            ->header('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function pdf(Request $request)
    {
        $data = $this->data($request, true);
        abort_if(count($data['missing']) > 0, 422, 'Complete your name, matric number, department, level and profile photo before downloading your ID card.');
        abort_if($data['expired'], 422, 'Your temporary ID card has expired. Contact student administration.');

        return Pdf::loadView('student.id-card.pdf', $data)
            ->setPaper([0, 0, 420, 264])
            ->setOption('isRemoteEnabled', false)
            ->download($data['filename'].'.pdf')
            ->header('Cache-Control', 'private, no-store');
    }
}
