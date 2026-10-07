<?php

namespace App\Http\Controllers;

use App\Models\{AcademicSession, User};
use App\Services\Academic\SessionProgression;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AcademicSessionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $years = AcademicSession::parseYears($data['name']);
        AcademicSession::create($data + $years + ['is_active' => AcademicSession::query()->doesntExist()]);
        return redirect()->route('dashboard')->with('success', 'Academic session created successfully.');
    }

    public function update(Request $request, AcademicSession $academicSession): RedirectResponse
    {
        $data = $this->validatePayload($request, $academicSession);
        if ($data['name'] !== $academicSession->name && (
            \App\Models\TuitionInvoice::where('academic_session_id', $academicSession->id)->exists()
            || DB::table('student_progressions')->where('from_session_id', $academicSession->id)->orWhere('to_session_id', $academicSession->id)->exists()
            || \App\Models\Result::where('session', $academicSession->name)->exists()
            || \App\Models\CourseRegistration::where('session', $academicSession->name)->exists()
            || \App\Models\StudentAcademicSession::where('academic_session_id', $academicSession->id)->exists()
        )) {
            throw ValidationException::withMessages(['name' => 'This session has academic or billing records. Create a new session instead of renaming it.']);
        }
        $academicSession->update($data + AcademicSession::parseYears($data['name']));
        return redirect()->route('dashboard')->with('success', 'Academic session updated successfully.');
    }

    public function review(AcademicSession $academicSession, SessionProgression $progression)
    {
        return view('admin.session-review', $progression->preview($academicSession));
    }

    public function activate(Request $request, AcademicSession $academicSession, SessionProgression $progression): RedirectResponse
    {
        $data = $request->validate(['preview_token' => 'required|string|size:64', 'confirmed' => 'accepted',
            'student_ids' => 'sometimes|array', 'student_ids.*' => 'integer|distinct']);
        DB::transaction(function () use ($request, $academicSession, $progression, $data) {
            \App\Models\PromotionPolicy::whereKey(1)->lockForUpdate()->firstOrFail();
            AcademicSession::orderBy('id')->lockForUpdate()->get();
            User::where('usertype', 'student')->orderBy('id')->lockForUpdate()->get();
            // Keep selected students' academic evidence stable through approval.
            \App\Models\Result::whereIn('user_id', $data['student_ids'] ?? [])->orderBy('id')->lockForUpdate()->get();
            \App\Models\CourseRegistration::whereIn('user_id', $data['student_ids'] ?? [])->orderBy('id')->lockForUpdate()->get();
            $preview = $progression->preview($academicSession->fresh());
            if (! hash_equals($preview['token'], $data['preview_token'])) {
                throw ValidationException::withMessages(['preview_token' => 'Enrollment, promotion policy or session details changed. Reload the activation review before continuing.']);
            }
            $rows = $preview['rows']->keyBy(fn ($row) => $row['student']->id);
            foreach ($rows as $row) {
                if ($preview['source']) {
                    \App\Services\Academic\StudentSessionLevel::snapshot($row['student'], $preview['source'], (string) $row['student']->level);
                }
            }
            foreach ($data['student_ids'] ?? [] as $id) {
                $row = $rows->get($id);
                if (! $row || ! $row['eligible']) {
                    throw ValidationException::withMessages(['student_ids' => 'A selected student is not eligible for this promotion. Review the checks before continuing.']);
                }
                $student = $row['student'];
                DB::table('student_progressions')->insert([
                    'user_id' => $student->id, 'from_session_id' => $preview['source']->id, 'to_session_id' => $academicSession->id,
                    'from_level' => $student->level, 'to_level' => $row['next'], 'approved_by' => $request->user()->id,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $student->update(['level' => $row['next']]);
            }
            foreach ($rows as $row) {
                $student = $row['student']->fresh();
                $existingLevel = \App\Models\StudentAcademicSession::where('user_id', $student->id)
                    ->where('academic_session_id', $academicSession->id)->value('level');
                if (in_array($student->id, $data['student_ids'] ?? []) && $existingLevel !== null && (string) $existingLevel !== (string) $student->level) {
                    throw ValidationException::withMessages(['student_ids' => 'Target session level conflicts with a saved student session record. Review session levels before activation.']);
                }
                \App\Services\Academic\StudentSessionLevel::snapshot($student, $academicSession, (string) $student->level);
            }
            AcademicSession::query()->update(['is_active' => false]);
            $academicSession->update(['is_active' => true]);
            app(\App\Services\TuitionBilling::class)->generateSession($academicSession);
            ActivityLogger::log($request->user(), 'academic_session_activated', 'Activated session after promotion review.', [
                'subject' => $academicSession, 'properties' => ['from_session_id' => $preview['source']?->id, 'promoted_student_ids' => $data['student_ids'] ?? []],
            ]);
        }, 3);
        return redirect()->route('dashboard')->with('success', "Active academic session set to {$academicSession->name}.");
    }

    protected function validatePayload(Request $request, ?AcademicSession $academicSession = null): array
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:9', Rule::unique('academic_sessions', 'name')->ignore($academicSession?->id)]]);
        if (! AcademicSession::isValidFormat($data['name'])) {
            throw ValidationException::withMessages(['name' => 'Academic session must be in the format 2021/2022 and the second year must be the next year.']);
        }
        return $data;
    }
}
