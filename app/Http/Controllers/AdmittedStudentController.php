<?php

namespace App\Http\Controllers;

use App\Models\AdmissionApplication;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdmittedStudentController extends Controller
{
    public const TYPES = [
        'fresh' => 'Fresh Student',
        'direct_entry' => 'Direct Entry',
        'transfer' => 'Transfer Student',
        'foreign' => 'Foreign Student',
    ];

    public const DOCUMENTS = [
        'jamb_result' => 'JAMB Result',
        'diploma_result' => 'Diploma Result/Certificate',
        'transcript' => 'Transcript',
    ];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'admission_type' => ['nullable', Rule::in(array_keys(self::TYPES))],
            'entry_year' => ['nullable', 'integer', 'between:1900,9999'],
        ]);

        $applications = AdmissionApplication::with(['user', 'department'])
            ->where('status', 'completed')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('application_number', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('matric_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['department_id'] ?? null, fn ($query, $value) => $query->where('department_id', $value))
            ->when($filters['admission_type'] ?? null, fn ($query, $value) => $query->where('admission_type', $value))
            ->when($filters['entry_year'] ?? null, fn ($query, $value) => $query->where('entry_year', $value))
            ->orderByDesc('completed_at')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('admin.admitted-students.index', [
            'applications' => $applications,
            'departments' => Department::orderBy('name')->get(),
            'types' => self::TYPES,
        ]);
    }

    public function show(AdmissionApplication $application)
    {
        abort_unless($application->status === 'completed', 404);
        $application->load(['user.department.faculty', 'department.faculty']);

        $documents = collect(self::DOCUMENTS)->map(function ($label, $key) use ($application) {
            $path = $application->{$key.'_path'};

            return ['label' => $label, 'available' => $path && Storage::disk('public')->exists($path)];
        });

        return view('admin.admitted-students.show', [
            'application' => $application, 'documents' => $documents, 'types' => self::TYPES,
        ]);
    }

    public function document(Request $request, AdmissionApplication $application, string $document)
    {
        abort_unless($application->status === 'completed' && isset(self::DOCUMENTS[$document]), 404);
        $path = $application->{$document.'_path'};
        abort_unless($path && Storage::disk('public')->exists($path), 404, 'Document unavailable.');

        $name = $document.'.'.pathinfo($path, PATHINFO_EXTENSION);
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];

        return $request->boolean('download')
            ? Storage::disk('public')->download($path, $name, $headers)
            : Storage::disk('public')->response($path, $name, $headers);
    }
}
