<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\User;
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

        AcademicSession::create([
            'name' => $data['name'],
            'start_year' => $years['start_year'],
            'end_year' => $years['end_year'],
            'is_active' => AcademicSession::query()->doesntExist(),
        ]);

        return redirect()->route('dashboard')->with('success', 'Academic session created successfully.');
    }

    public function update(Request $request, AcademicSession $academicSession): RedirectResponse
    {
        $data = $this->validatePayload($request, $academicSession);
        $years = AcademicSession::parseYears($data['name']);
        $previousStartYear = (int) $academicSession->start_year;
        $wasActive = (bool) $academicSession->is_active;

        DB::transaction(function () use ($academicSession, $data, $years, $previousStartYear, $wasActive) {
            $academicSession->update([
                'name' => $data['name'],
                'start_year' => $years['start_year'],
                'end_year' => $years['end_year'],
            ]);

            if ($wasActive) {
                $this->promoteStudentsForSessionAdvance($previousStartYear, (int) $years['start_year']);
            }
        });

        return redirect()->route('dashboard')->with('success', 'Academic session updated successfully.');
    }

    public function activate(AcademicSession $academicSession): RedirectResponse
    {
        $currentActiveSession = AcademicSession::current();

        DB::transaction(function () use ($academicSession, $currentActiveSession) {
            AcademicSession::query()->update(['is_active' => false]);
            $academicSession->update(['is_active' => true]);

            if ($currentActiveSession && $currentActiveSession->id !== $academicSession->id) {
                $this->promoteStudentsForSessionAdvance(
                    (int) $currentActiveSession->start_year,
                    (int) $academicSession->start_year
                );
            }
        });

        return redirect()->route('dashboard')->with('success', "Active academic session set to {$academicSession->name}.");
    }

    protected function validatePayload(Request $request, ?AcademicSession $academicSession = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:9',
                Rule::unique('academic_sessions', 'name')->ignore($academicSession?->id),
            ],
        ]);

        if (!AcademicSession::isValidFormat($data['name'])) {
            throw ValidationException::withMessages([
                'name' => 'Academic session must be in the format 2021/2022 and the second year must be the next year.',
            ]);
        }

        return $data;
    }

    protected function promoteStudentsForSessionAdvance(int $fromStartYear, int $toStartYear): void
    {
        $yearDelta = $toStartYear - $fromStartYear;

        if ($yearDelta <= 0) {
            return;
        }

        User::query()
            ->where('usertype', 'student')
            ->whereNotNull('level')
            ->whereIn('level', ['100', '200', '300', '400', '500'])
            ->orderBy('id')
            ->chunkById(200, function ($students) use ($yearDelta) {
                foreach ($students as $student) {
                    $promotedLevel = min(500, ((int) $student->level) + ($yearDelta * 100));

                    if ((int) $student->level !== $promotedLevel) {
                        $student->update(['level' => (string) $promotedLevel]);
                    }
                }
            });
    }
}
