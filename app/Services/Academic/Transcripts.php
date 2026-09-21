<?php

namespace App\Services\Academic;

use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Result;
use App\Models\TranscriptDocument;
use App\Models\TranscriptRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Transcripts
{
    public static function issue(User $student, int $departmentId, User $actor, ?string $session = null, ?string $semester = null, ?TranscriptRequest $request = null): TranscriptDocument
    {
        abort_unless(ResultAccess::manager($actor) || ($actor->dashboardRole() === 'student' && $actor->id === $student->id && ! $request), 403);
        $path = null;
        try {
            return DB::transaction(function () use ($student, $departmentId, $actor, $session, $semester, $request, &$path) {
                User::whereKey($student->id)->lockForUpdate()->firstOrFail();
                if ($request) {
                    $request = TranscriptRequest::lockForUpdate()->findOrFail($request->id);
                    if ($request->status !== 'pending') {
                        throw ValidationException::withMessages(['request' => 'This request has already been decided.']);
                    }
                }
                $results = Result::where('user_id', $student->id)->where('department_id', $departmentId)
                    ->where('workflow_status', 'published')
                    ->when($session, fn ($q) => $q->where('session', $session))
                    ->when($semester, fn ($q) => $q->where('semester', $semester))
                    ->orderBy('session')->orderBy('semester')->orderBy('course_code')->lockForUpdate()->get();
                if ($results->isEmpty()) {
                    throw ValidationException::withMessages(['transcript' => 'No published results are available.']);
                }
                if ($results->contains(fn ($r) => in_array($r->outcome_status, ['withheld', 'incomplete', 'not_submitted'], true))) {
                    throw ValidationException::withMessages(['transcript' => 'Resolve withheld or incomplete results before issuing a transcript.']);
                }
                if ($request) {
                    $unpublished = Result::where('user_id', $student->id)->where('department_id', $departmentId)
                        ->where('workflow_status', '!=', 'published')
                        ->when($session, fn ($q) => $q->where('session', $session))
                        ->when($semester, fn ($q) => $q->where('semester', $semester))->exists();
                    $missing = CourseRegistration::with('course')->where('user_id', $student->id)
                        ->whereIn('status', ['registered', 'approved', 'completed'])
                        ->whereHas('course', fn ($q) => $q->where('department_id', $departmentId))
                        ->when($session, fn ($q) => $q->where('session', $session))
                        ->when($semester, fn ($q) => $q->where('semester', $semester))->get()
                        ->contains(fn ($registration) => ! $results->contains(fn ($r) => $r->course_code === $registration->course->code && $r->session === $registration->session && $r->semester === $registration->semester));
                    if ($unpublished || $missing) {
                        throw ValidationException::withMessages(['transcript' => 'Official issuance requires complete, published results for the registered courses.']);
                    }
                }
                $code = (string) Str::uuid();
                $path = 'documents/transcripts/'.$code.'.pdf';
                $document = TranscriptDocument::create([
                    'transcript_request_id' => $request?->id, 'user_id' => $student->id, 'department_id' => $departmentId,
                    'verification_code' => $code, 'path' => $path,
                    'version' => 1 + (int) TranscriptDocument::where('user_id', $student->id)->where('department_id', $departmentId)->max('version'),
                    'official' => $request !== null, 'status' => 'valid', 'issued_by' => $actor->id,
                    'result_versions' => $results->pluck('version', 'id')->all(),
                ]);
                $asOf = $session ?? $results->last()->session;
                $standing = AcademicStanding::report($student, $departmentId, $asOf, $semester);
                $pdf = Pdf::loadView('academic.transcript-pdf', [
                    'document' => $document, 'student' => $student, 'department' => Department::findOrFail($departmentId),
                    'groups' => $results->groupBy(fn ($r) => $r->session.' '.$r->semester), 'standing' => $standing,
                ])->output();
                if (! Storage::disk('local')->put($path, $pdf)) {
                    throw new \RuntimeException('Could not store transcript.');
                }
                $document->update(['sha256' => hash('sha256', $pdf)]);
                if ($request) {
                    $request->update(['status' => 'issued', 'decided_by' => $actor->id]);
                }
                Result::whereIn('id', $results->pluck('id'))->update([
                    $session ? 'transcript_path' : 'full_transcript_path' => route('documents.transcripts.show', ['filename' => basename($path)], false),
                ]);

                return $document;
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
    }

    public static function download(TranscriptDocument $document, User $actor)
    {
        abort_unless(ResultAccess::manager($actor) || ($actor->dashboardRole() === 'student' && $actor->id === $document->user_id), 403);
        abort_unless($document->status === 'valid', 410, 'This transcript has been revoked.');
        $current = Result::whereIn('id', array_keys($document->result_versions))->where('workflow_status', 'published')->pluck('version', 'id')->all();
        abort_unless($current == $document->result_versions, 410, 'The source results have changed. Request a new transcript.');
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, 'transcript-'.$document->verification_code.'.pdf', [
            'Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
