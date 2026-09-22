<?php

namespace App\Http\Controllers;

use App\Models\AdmissionApplication;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdmissionController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();

        if (! $user->isAdmissionApplicant()) {
            return redirect()->route('dashboard');
        }
        $application = AdmissionApplication::with('department')
            ->where('user_id', $user->id)
            ->latest()
            ->first();
        $departments = Department::with('faculty')
            ->orderBy('name')
            ->get();

        return view('admissions.create', compact('application', 'departments', 'user'));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user->isAdmissionApplicant()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'surname' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['Female', 'Male', 'Other'])],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['required_if:admission_type,foreign', 'nullable', 'string', 'max:120'],
            'parent_name' => ['required', 'string', 'max:255'],
            'parent_relationship' => ['required', 'string', 'max:120'],
            'parent_phone' => ['required', 'string', 'max:30'],
            'parent_email' => ['nullable', 'email', 'max:255'],
            'parent_address' => ['nullable', 'string', 'max:500'],
            'admission_type' => ['required', Rule::in(['fresh', 'direct_entry', 'transfer', 'foreign'])],
            'department_id' => ['required', 'exists:departments,id'],
            'level' => ['required', Rule::in(['100', '200', '300', '400', '500', '600'])],
            'entry_year' => ['required', 'integer', 'min:1900', 'max:' . now()->year],
            'previous_school' => ['required', 'string', 'max:255'],
            'qualification' => ['required', Rule::in(['Olevel', 'ND', 'Diploma'])],
            'olevel_subjects' => ['exclude_unless:qualification,Olevel', 'required', 'array', 'min:1', 'max:40'],
            'olevel_subjects.*' => ['required', 'string', 'max:120', 'distinct:ignore_case'],
            'olevel_grades' => ['exclude_unless:qualification,Olevel', 'required', 'array', 'min:1', 'max:40'],
            'olevel_grades.*' => ['required', Rule::in(['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9'])],
            'graduation_year' => ['nullable', 'integer', 'min:1900', 'max:' . now()->year],
            'academic_notes' => ['nullable', 'string', 'max:2000'],
            'jamb_result' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'diploma_result' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'transcript' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $olevelResults = $this->formatOlevelResults(
            $validated['qualification'],
            $validated['olevel_subjects'] ?? [],
            $validated['olevel_grades'] ?? []
        );

        if ($validated['qualification'] === 'Olevel' && (
            empty($olevelResults)
            || array_keys($validated['olevel_subjects']) !== array_keys($validated['olevel_grades'])
        )) {
            return back()
                ->withErrors(['olevel_subjects' => 'Please provide a matching grade for every Olevel subject.'])
                ->withInput();
        }

        unset($validated['olevel_subjects'], $validated['olevel_grades'], $validated['jamb_result'], $validated['diploma_result'], $validated['transcript']);

        DB::transaction(function () use ($request, $user, $validated, $olevelResults) {
            $existingApplication = AdmissionApplication::where('user_id', $user->id)->first();
            $documents = $this->storeDocuments($request, $existingApplication);

            $application = AdmissionApplication::updateOrCreate(
                ['user_id' => $user->id],
                $validated + [
                    'olevel_results' => $olevelResults,
                    'application_number' => $this->applicationNumber($user->id),
                    'status' => 'completed',
                    'completed_at' => now(),
                ] + $documents
            );

            $user->forceFill([
                'name' => trim($application->first_name . ' ' . $application->middle_name . ' ' . $application->surname),
                'usertype' => 'student',
                'department_id' => $application->department_id,
                'level' => $application->level,
                'entry_year' => $application->entry_year,
            ])->save();
        });

        return redirect()
            ->route('dashboard')
            ->with('success', 'Admission completed. Your account has been converted to a student account.');
    }

    private function applicationNumber(int $userId): string
    {
        return 'ADM-' . now()->format('Y') . '-' . str_pad((string) $userId, 5, '0', STR_PAD_LEFT);
    }

    private function formatOlevelResults(string $qualification, array $subjects, array $grades): ?array
    {
        if ($qualification !== 'Olevel') {
            return null;
        }

        return collect($subjects)
            ->map(function ($subject, $index) use ($grades) {
                $subject = trim((string) $subject);
                $grade = $grades[$index] ?? null;

                if ($subject === '' || blank($grade)) {
                    return null;
                }

                return [
                    'subject' => $subject,
                    'grade' => $grade,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function storeDocuments(Request $request, ?AdmissionApplication $application): array
    {
        $documents = [];
        $fields = [
            'jamb_result' => 'jamb_result_path',
            'diploma_result' => 'diploma_result_path',
            'transcript' => 'transcript_path',
        ];

        foreach ($fields as $input => $column) {
            if (! $request->hasFile($input)) {
                if ($application?->{$column}) {
                    $documents[$column] = $application->{$column};
                }

                continue;
            }

            if ($application?->{$column}) {
                Storage::disk('public')->delete($application->{$column});
            }

            $documents[$column] = $request->file($input)->store('admission-documents', 'public');
        }

        return $documents;
    }
}
