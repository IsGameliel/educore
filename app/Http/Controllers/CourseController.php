<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CourseImport;
use DataTables; // yajra
use App\Exports\CoursesExport;
use App\Models\{
    AcademicSession, Department, Courses
};
use App\Support\ActivityLogger;


class CourseController extends Controller
{
    /**
     * Display a listing of the courses.
     */
    public function index(Request $request)
    {
        $departments = Department::orderBy('name')->get(['id','name']);
        $academicSessions = AcademicSession::orderByDesc('start_year')->get(['id', 'name']);

        $departmentId = $request->input('department_id', $request->input('department'));

        $query = Courses::with(['department', 'academicSession'])->latest('id');

        if ($request->filled('title')) {
            $query->where('title', 'like', "%{$request->title}%");
        }

        if (filled($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        if ($request->filled('academic_session_id')) {
            $query->where('academic_session_id', $request->academic_session_id);
        }

        $courses = $query->paginate(15)->withQueryString();

        return view('admin.courses.index', compact('courses', 'departments', 'academicSessions'));
    }




    /**
     * Show the form for creating a new course.
     */
    public function create()
    {
        // Pass departments for dropdown selection in form
        $departments = Department::all();
        $academicSessions = AcademicSession::orderByDesc('start_year')->get();
        $defaultAcademicSessionId = old('academic_session_id', AcademicSession::current()?->id);

        return view('admin.courses.create', compact('departments', 'academicSessions', 'defaultAcademicSessionId'));
    }

    /**
     * Store a newly created course in storage.
     */
    public function store(Request $request)
    {
        // Validate input data
        $validated = $request->validate([
            'code' => 'required|string|max:10',
            'title' => 'required|string|max:255',
            'credit_unit' => 'required|integer|min:1|max:10',
            'semester' => 'required|string|in:First,Second',
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id',
            'level' => 'required|int|max:535',
            'academic_session_id' => 'required|exists:academic_sessions,id',
        ]);

        $createdCourses = collect();

        DB::transaction(function () use ($validated, &$createdCourses) {
            foreach (array_unique($validated['department_ids']) as $departmentId) {
                $course = Courses::firstOrCreate(
                    [
                        'code' => $validated['code'],
                        'semester' => $validated['semester'],
                        'level' => $validated['level'],
                        'department_id' => $departmentId,
                        'academic_session_id' => $validated['academic_session_id'],
                    ],
                    [
                        'title' => $validated['title'],
                        'credit_unit' => $validated['credit_unit'],
                    ]
                );

                if ($course->wasRecentlyCreated) {
                    $createdCourses->push($course);
                }
            }
        });

        $createdCount = $createdCourses->count();

        if ($createdCount === 0) {
            return redirect()->route('admin.courses.index')->with('warning', 'Matching course records already exist for the selected department(s).');
        }

        $createdCourses->each(function (Courses $course) {
            ActivityLogger::log(
                Auth::user(),
                'course_created',
                "Added {$course->code} - {$course->title} ({$course->credit_unit} unit(s)) for {$course->level} Level",
                [
                    'subject' => $course,
                    'department_id' => $course->department_id,
                    'properties' => [
                        'course_id' => $course->id,
                        'course_code' => $course->code,
                        'course_title' => $course->title,
                        'credit_unit' => $course->credit_unit,
                        'semester' => $course->semester,
                        'level' => (string) $course->level,
                        'academic_session_id' => $course->academic_session_id,
                        'academic_session' => $course->academicSession?->name,
                    ],
                ]
            );
        });

        $departmentTotal = count(array_unique($validated['department_ids']));
        $message = $createdCount === 1
            ? 'Course created successfully for 1 department.'
            : "Course created successfully for {$createdCount} departments.";

        if ($createdCount < $departmentTotal) {
            $message .= ' Existing matching course records were skipped.';
        }

        return redirect()->route('admin.courses.index')->with('success', $message);
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit(Courses $course)
    {
        $departments = Department::all();
        $academicSessions = AcademicSession::orderByDesc('start_year')->get();
        $selectedDepartmentIds = Courses::query()
            ->where('code', $course->code)
            ->where('title', $course->title)
            ->where('credit_unit', $course->credit_unit)
            ->where('semester', $course->semester)
            ->where('level', $course->level)
            ->where('academic_session_id', $course->academic_session_id)
            ->pluck('department_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($selectedDepartmentIds) && $course->department_id) {
            $selectedDepartmentIds = [$course->department_id];
        }

        return view('admin.courses.edit', compact('course', 'departments', 'academicSessions', 'selectedDepartmentIds'));
    }

    /**
     * Update the specified course in storage.
     */
    public function update(Request $request, Courses $course)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10',
            'title' => 'required|string|max:255',
            'credit_unit' => 'required|integer|min:1|max:10',
            'semester' => 'required|string|in:First,Second',
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id',
            'level' => 'required|int|max:535',
            'academic_session_id' => 'required|exists:academic_sessions,id',
        ]);

        $selectedDepartmentIds = array_values(array_unique($validated['department_ids']));
        $updatedCourses = collect();
        $createdCourses = collect();

        $originalSignature = [
            'code' => $course->code,
            'title' => $course->title,
            'credit_unit' => $course->credit_unit,
            'semester' => $course->semester,
            'level' => $course->level,
            'academic_session_id' => $course->academic_session_id,
        ];

        DB::transaction(function () use ($course, $validated, $selectedDepartmentIds, $originalSignature, &$updatedCourses, &$createdCourses) {
            $existingCourses = Courses::query()
                ->where($originalSignature)
                ->get()
                ->keyBy('department_id');

            foreach ($selectedDepartmentIds as $index => $departmentId) {
                $targetCourse = $existingCourses->get($departmentId);

                if (! $targetCourse && $departmentId === $course->department_id) {
                    $targetCourse = $course;
                }

                $payload = [
                    'code' => $validated['code'],
                    'title' => $validated['title'],
                    'credit_unit' => $validated['credit_unit'],
                    'semester' => $validated['semester'],
                    'department_id' => $departmentId,
                    'level' => $validated['level'],
                    'academic_session_id' => $validated['academic_session_id'],
                ];

                if ($targetCourse) {
                    $originalValues = $targetCourse->only(['code', 'title', 'credit_unit', 'semester', 'department_id', 'level', 'academic_session_id']);
                    $targetCourse->update($payload);
                    $updatedCourses->push([
                        'course' => $targetCourse->fresh(),
                        'original' => $originalValues,
                    ]);
                    continue;
                }

                $createdCourses->push(Courses::create($payload));
            }
        });

        $updatedCount = $updatedCourses->count();
        $createdCount = $createdCourses->count();

        $updatedCourses->each(function (array $entry) {
            /** @var Courses $course */
            $course = $entry['course'];
            $original = $entry['original'];
            $unitChanged = (int) ($original['credit_unit'] ?? 0) !== (int) $course->credit_unit;
            $description = $unitChanged
                ? "Updated {$course->code} - {$course->title} course unit from {$original['credit_unit']} to {$course->credit_unit}"
                : "Updated {$course->code} - {$course->title}";

            ActivityLogger::log(
                Auth::user(),
                'course_updated',
                $description,
                [
                    'subject' => $course,
                    'department_id' => $course->department_id,
                    'properties' => [
                        'course_id' => $course->id,
                        'course_code' => $course->code,
                        'course_title' => $course->title,
                        'credit_unit' => $course->credit_unit,
                        'old_credit_unit' => $original['credit_unit'] ?? null,
                        'semester' => $course->semester,
                        'level' => (string) $course->level,
                        'academic_session_id' => $course->academic_session_id,
                        'academic_session' => $course->academicSession?->name,
                    ],
                ]
            );
        });

        $createdCourses->each(function (Courses $course) {
            ActivityLogger::log(
                Auth::user(),
                'course_created',
                "Added {$course->code} - {$course->title} ({$course->credit_unit} unit(s)) for {$course->level} Level",
                [
                    'subject' => $course,
                    'department_id' => $course->department_id,
                    'properties' => [
                        'course_id' => $course->id,
                        'course_code' => $course->code,
                        'course_title' => $course->title,
                        'credit_unit' => $course->credit_unit,
                        'semester' => $course->semester,
                        'level' => (string) $course->level,
                        'academic_session_id' => $course->academic_session_id,
                        'academic_session' => $course->academicSession?->name,
                    ],
                ]
            );
        });

        $message = 'Course updated successfully.';

        if ($createdCount > 0) {
            $message .= " Added to {$createdCount} additional department(s).";
        }

        $message .= ' Departments not selected were left unchanged.';

        return redirect()->route('admin.courses.index')->with('success', $message);
    }

    /**
     * Remove the specified course from storage.
     */
    public function destroy(Courses $course)
    {
        $course->delete();

        return redirect()->route('admin.courses.index')->with('success', 'Course deleted successfully!');
    }

    public function assignPrerequisites(Request $request, Courses $course)
    {
        // Validate the input
        $request->validate([
            'prerequisites' => 'required|array',
            'prerequisites.*' => 'exists:courses,id',
        ]);

        // Attach prerequisites
        $course->prerequisites()->sync($request->prerequisites);

        return redirect()->back()->with('success', 'Prerequisites updated successfully!');
    }

    public function showPrerequisites(Courses $course)
    {
        // Fetch prerequisites and all courses
        $prerequisites = $course->prerequisites;
        $allCourses = Courses::all();

        return view('admin.courses.prerequisites', compact('course', 'prerequisites', 'allCourses'));
    }

    public function showImportForm()
    {
        return view('admin.courses.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new CourseImport(Auth::user());

        Excel::import($import, $request->file('file'));

        $message = "{$import->createdCount()} course record(s) imported successfully.";

        if ($import->skippedCount() > 0) {
            $message .= " {$import->skippedCount()} existing course record(s) were updated or skipped.";
        }

        if ($import->failedRows()) {
            return redirect()
                ->route('admin.courses.import.form')
                ->with('warning', $message)
                ->with('import_errors', $import->failedRows());
        }

        return redirect()->route('admin.courses.index')->with('success', $message);
    }

}
