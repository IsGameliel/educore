<?php

namespace App\Http\Controllers;

use App\Imports\ResultsImport;
use App\Models\Courses;
use App\Models\Result;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Support\ActivityLogger;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $query = Result::with(['user.department', 'department']);
        $actor = Auth::user();

        if ($actor->usertype === 'student') {
            $query->where('user_id', Auth::id())
                ->where('department_id', $actor->department_id);
        } elseif ($actor->usertype === 'lecturer') {
            $this->applyAccessibleCourseScope($query, $actor);
        }

        if ($request->filled('user_id') && $actor->usertype !== 'student') {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('session')) {
            $query->where('session', $request->session);
        }
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        $results = $query->get();
        $students = User::where('usertype', 'student')->get();
        $departments = Department::all();
        $view = $actor->usertype === 'student' ? 'student.result.index' : 'admin.result.index';

        return view($view, compact('results', 'students', 'departments'));
    }

    public function export(Request $request)
    {
        $query = Result::with(['user', 'department']);
        $actor = Auth::user();

        if ($actor->usertype === 'lecturer') {
            $this->applyAccessibleCourseScope($query, $actor);
        }

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('department_id') && $request->department_id) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->has('session') && $request->session) {
            $query->bySessionAndSemester($request->session, $request->semester ?? '');
        }
        if ($request->has('level') && $request->level) {
            $query->where('level', $request->level);
        }

        $results = $query->get();

        // Prepare data for export
        $exportData = $results->map(function($result) {
            return [
                'Student Name'   => $result->user->name,
                'Matric Number'  => $result->matric_number,
                'Department'     => $result->department->name ?? '',
                'Session'        => $result->session,
                'Semester'       => $result->semester,
                'Level'          => $result->level,
                'Course Code'    => $result->course_code,
                'Course Title'   => $result->course_title,
                'CA'             => $result->ca_score,
                'Exam'           => $result->exam_score,
                'Score'          => $result->score,
                'Grade'          => $result->grade,
            ];
        });

        return Excel::download(new \App\Exports\ArrayExport($exportData->toArray()), 'filtered_results.xlsx');
    }

    public function show(Request $request, $userId, $session, $semester)
    {
        $user = User::findOrFail($userId);
        $session = urldecode($session);
        $departmentId = $this->resolveDepartmentId($request, $user);

        if (Auth::user()->usertype === 'student' && Auth::id() !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $resultsQuery = Result::bySessionAndSemester($session, $semester)
            ->where('user_id', $userId)
            ->where('department_id', $departmentId);

        if (Auth::user()->usertype === 'lecturer') {
            $this->applyAccessibleCourseScope($resultsQuery, Auth::user());
        }

        $results = $resultsQuery->get();

        if ($results->isEmpty()) {
            $route = Auth::user()->usertype === 'student' ? 'student.results.index' : 'admin.results.index';
            return redirect()->route($route)->with('error', 'No results found.');
        }

        $totalCreditUnits = $results->sum('credit_unit');
        $weightedSum = $results->sum(fn($result) => $result->credit_unit * $result->grade_point);
        $gpa = $totalCreditUnits > 0 ? round($weightedSum / $totalCreditUnits, 2) : 0;

        $allResultsQuery = Result::where('user_id', $userId)
            ->where('department_id', $departmentId);

        if (Auth::user()->usertype === 'lecturer') {
            $this->applyAccessibleCourseScope($allResultsQuery, Auth::user());
        }

        $allResults = $allResultsQuery->get();
        $totalAllCreditUnits = $allResults->sum('credit_unit');
        $totalWeightedSum = $allResults->sum(fn($result) => $result->credit_unit * $result->grade_point);
        $cgpa = $totalAllCreditUnits > 0 ? round($totalWeightedSum / $totalAllCreditUnits, 2) : null;

        return view('student.result.show', compact(
            'user', 'results', 'totalCreditUnits', 'gpa', 'cgpa', 'session', 'semester', 'departmentId'
        ));
    }

    public function create()
    {
        $actor = Auth::user();
        $courses = $this->getAccessibleCoursesForUser($actor);
        $departmentIds = $courses->pluck('department_id')->unique()->values();
        $departments = $this->isPrivilegedResultManager($actor)
            ? Department::all()
            : Department::whereIn('id', $departmentIds)->get();
        $students = User::where('usertype', 'student')
            ->when(!$this->isPrivilegedResultManager($actor), fn ($query) => $query->whereIn('department_id', $departmentIds))
            ->get();

        return view('admin.result.create', compact('students', 'departments', 'courses'));
    }

    public function editGroup(Request $request, $user_id, $session, $semester)
    {
        $student = User::findOrFail($user_id);
        $session = urldecode($session);
        $departmentId = $this->resolveDepartmentId($request, $student);
        $results = Result::where('user_id', $user_id)
            ->where('session', $session)
            ->where('semester', $semester)
            ->where('department_id', $departmentId);

        if (Auth::user()->usertype === 'lecturer') {
            $this->applyAccessibleCourseScope($results, Auth::user());
        }

        $results = $results->get();

        $students = User::where('usertype', 'student')->get();
        $departments = Department::all();

        return view('admin.result.edit-group', compact('results', 'students', 'departments', 'user_id', 'session', 'semester', 'departmentId'));
    }

    public function getStudentsByDepartment($department_id)
    {
        if (Auth::user()->usertype === 'lecturer' && !$this->getAccessibleCoursesForUser(Auth::user())->where('department_id', (int) $department_id)->isNotEmpty()) {
            abort(403, 'Unauthorized');
        }

        $students = User::where('usertype', 'student')
            ->where('department_id', $department_id)
            ->get(['id', 'name', 'matric_number', 'level']);

        return response()->json($students);
    }


    public function store(Request $request)
    {
        $actor = Auth::user();
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'session' => 'required|string|max:255',
            'semester' => 'required|in:First,Second',
            'course_id' => 'required|exists:courses,id',
            'ca_score' => 'nullable|numeric|min:0|max:100',
            'exam_score' => 'nullable|numeric|min:0|max:100',
            'score' => 'nullable|numeric|min:0|max:100',
            'department_id' => 'required|numeric|min:0',
        ]);

        $user = User::where('usertype', 'student')->findOrFail($request->user_id);
        $course = $this->resolveManagedCourse($request->course_id);
        $department = Department::findOrFail($course->department_id);

        if ((int) $request->department_id !== (int) $course->department_id) {
            throw ValidationException::withMessages([
                'department_id' => 'The selected department does not match the selected course.',
            ]);
        }

        $score = $this->resolveResultScore(
            $request->score,
            $request->ca_score,
            $request->exam_score
        );
        $gradeData = Result::calculateGradeAndPoint($score, $department->pass_mark);

        $result = Result::create([
            'user_id' => $request->user_id,
            'uploaded_by' => $actor->id,
            'matric_number' => $user->matric_number,
            'session' => $request->session,
            'semester' => $request->semester,
            'level' => $user->level,
            'course_code' => $course->code,
            'course_title' => $course->title,
            'credit_unit' => $course->credit_unit,
            'ca_score' => $request->filled('ca_score') ? $request->ca_score : null,
            'exam_score' => $request->filled('exam_score') ? $request->exam_score : null,
            'score' => $score,
            'grade' => $gradeData['grade'],
            'grade_point' => $gradeData['grade_point'],
            'source_result_id' => null,
            'department_id' => $course->department_id,
        ]);

        ActivityLogger::log(
            $actor,
            'result_uploaded',
            "Uploaded result for {$user->name} in {$course->code} ({$request->semester} Semester, {$request->session})",
            [
                'subject' => $result,
                'target_user' => $user,
                'department_id' => $course->department_id,
                'properties' => [
                    'course_id' => $course->id,
                    'course_code' => $course->code,
                    'course_title' => $course->title,
                    'semester' => $request->semester,
                    'session' => $request->session,
                ],
            ]
        );

        return redirect()->route('admin.results.index')->with('success', 'Result added successfully.');
    }

    public function edit(Result $result)
    {
        $this->ensureCanManageResultRecord($result);
        $students = User::where('usertype', 'student')->get();
        return view('admin.result.edit', compact('result', 'students'));
    }

    public function update(Request $request, Result $result)
    {
        $actor = Auth::user();
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'session' => 'required|string|max:255',
            'semester' => 'required|in:First,Second',
            'course_code' => 'required|string|max:255',
            'course_title' => 'required|string|max:255',
            'credit_unit' => 'required|integer|min:1',
            'ca_score' => 'nullable|numeric|min:0|max:100',
            'exam_score' => 'nullable|numeric|min:0|max:100',
            'score' => 'nullable|numeric|min:0|max:100',
            'department_id' => 'nullable|numeric|min:0',
        ]);

        $this->ensureCanManageResultRecord($result);
        $user = User::where('usertype', 'student')->findOrFail($request->user_id);
        $department = Department::findOrFail($request->department_id ?? $result->department_id ?? $user->department_id);
        $this->ensureCourseCodeIsManageable($request->course_code, $department->id);
        $score = $this->resolveResultScore(
            $request->score,
            $request->ca_score,
            $request->exam_score
        );
        $gradeData = Result::calculateGradeAndPoint($score, $department->pass_mark);
        $oldCourseCode = $result->course_code;
        $oldSemester = $result->semester;
        $oldSession = $result->session;

        $result->update([
            'user_id' => $request->user_id,
            'matric_number' => $user->matric_number,
            'session' => $request->session,
            'semester' => $request->semester,
            'level' => $user->level,
            'course_code' => $request->course_code,
            'course_title' => $request->course_title,
            'credit_unit' => $request->credit_unit,
            'ca_score' => $request->filled('ca_score') ? $request->ca_score : null,
            'exam_score' => $request->filled('exam_score') ? $request->exam_score : null,
            'score' => $score,
            'grade' => $gradeData['grade'],
            'grade_point' => $gradeData['grade_point'],
            'source_result_id' => $result->source_result_id,
            'department_id' => $department->id,
        ]);

        ActivityLogger::log(
            $actor,
            'result_updated',
            "Updated result for {$user->name}: {$oldCourseCode} ({$oldSemester} Semester, {$oldSession}) to {$request->course_code} ({$request->semester} Semester, {$request->session})",
            [
                'subject' => $result,
                'target_user' => $user,
                'department_id' => $department->id,
                'properties' => [
                    'course_code' => $request->course_code,
                    'course_title' => $request->course_title,
                    'semester' => $request->semester,
                    'session' => $request->session,
                ],
            ]
        );

        return redirect()->route('admin.results.index')->with('success', 'Result updated successfully.');
    }

    public function updateGroup(Request $request, $user_id, $session, $semester)
    {
        $actor = Auth::user();
        $validated = $request->validate([
            'results' => 'required|array',
            'results.*.course_code' => 'required|string|max:255',
            'results.*.course_title' => 'required|string|max:255',
            'results.*.credit_unit' => 'required|integer|min:1',
            'results.*.ca_score' => 'nullable|numeric|min:0|max:100',
            'results.*.exam_score' => 'nullable|numeric|min:0|max:100',
            'results.*.score' => 'nullable|numeric|min:0|max:100',
        ]);

        $student = User::where('usertype', 'student')->findOrFail($user_id);
        $session = urldecode($session);
        $departmentId = $this->resolveDepartmentId($request, $student);
        $department = Department::findOrFail($departmentId);

        foreach ($validated['results'] as $resultId => $payload) {
            $result = Result::where('id', $resultId)
                ->where('user_id', $user_id)
                ->where('session', $session)
                ->where('semester', $semester)
                ->where('department_id', $departmentId)
                ->firstOrFail();

            $this->ensureCanManageResultRecord($result);
            $this->ensureCourseCodeIsManageable($payload['course_code'], $departmentId);

            $score = $this->resolveResultScore(
                $payload['score'] ?? null,
                $payload['ca_score'] ?? null,
                $payload['exam_score'] ?? null
            );
            $gradeData = Result::calculateGradeAndPoint($score, $department->pass_mark);

            $result->update([
                'course_code' => $payload['course_code'],
                'course_title' => $payload['course_title'],
                'credit_unit' => $payload['credit_unit'],
                'ca_score' => array_key_exists('ca_score', $payload) && $payload['ca_score'] !== '' ? $payload['ca_score'] : null,
                'exam_score' => array_key_exists('exam_score', $payload) && $payload['exam_score'] !== '' ? $payload['exam_score'] : null,
                'score' => $score,
                'grade' => $gradeData['grade'],
                'grade_point' => $gradeData['grade_point'],
                'department_id' => $department->id,
            ]);

            ActivityLogger::log(
                $actor,
                'result_updated',
                "Updated result for {$student->name} in {$payload['course_code']} ({$semester} Semester, {$session})",
                [
                    'subject' => $result,
                    'target_user' => $student,
                    'department_id' => $department->id,
                    'properties' => [
                        'course_code' => $payload['course_code'],
                        'course_title' => $payload['course_title'],
                        'semester' => $semester,
                        'session' => $session,
                    ],
                ]
            );
        }

        return redirect()->route('admin.results.editGroup', [
            $user_id,
            $session,
            $semester,
            'department_id' => $departmentId,
        ])
            ->with('success', 'Results updated successfully.');
    }

    public function migrateDepartmentResults(Request $request, $userId)
    {
        if (!$this->isPrivilegedResultManager(Auth::user())) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'source_department_id' => 'required|exists:departments,id',
            'target_department_id' => 'required|exists:departments,id|different:source_department_id',
        ]);

        $student = User::where('usertype', 'student')->findOrFail($userId);
        $sourceDepartmentId = (int) $validated['source_department_id'];
        $targetDepartmentId = (int) $validated['target_department_id'];

        $sourceResults = Result::where('user_id', $student->id)
            ->where('department_id', $sourceDepartmentId)
            ->orderBy('session')
            ->orderByRaw("FIELD(semester, 'First', 'Second')")
            ->get();

        if ($sourceResults->isEmpty()) {
            return redirect()->route('admin.results.index')
                ->with('error', 'No results were found in the selected source department.');
        }

        $copiedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($sourceResults, $targetDepartmentId, &$copiedCount, &$skippedCount) {
            foreach ($sourceResults as $sourceResult) {
                $alreadyExists = Result::where('user_id', $sourceResult->user_id)
                    ->where('department_id', $targetDepartmentId)
                    ->where(function ($query) use ($sourceResult) {
                        $query->where('source_result_id', $sourceResult->id)
                            ->orWhere(function ($nestedQuery) use ($sourceResult) {
                                $nestedQuery->whereNull('source_result_id')
                                    ->where('session', $sourceResult->session)
                                    ->where('semester', $sourceResult->semester)
                                    ->where('level', $sourceResult->level)
                                    ->where('course_code', $sourceResult->course_code)
                                    ->where('course_title', $sourceResult->course_title);
                            });
                    })
                    ->exists();

                if ($alreadyExists) {
                    $skippedCount++;
                    continue;
                }

                Result::create([
                    'user_id' => $sourceResult->user_id,
                    'matric_number' => $sourceResult->matric_number,
                    'session' => $sourceResult->session,
                    'semester' => $sourceResult->semester,
                    'level' => $sourceResult->level,
                    'course_code' => $sourceResult->course_code,
                    'course_title' => $sourceResult->course_title,
                    'credit_unit' => $sourceResult->credit_unit,
                    'ca_score' => $sourceResult->ca_score,
                    'exam_score' => $sourceResult->exam_score,
                    'score' => $sourceResult->score,
                    'grade' => $sourceResult->grade,
                    'grade_point' => $sourceResult->grade_point,
                    'source_result_id' => $sourceResult->id,
                    'department_id' => $targetDepartmentId,
                    'transcript_path' => null,
                ]);

                $copiedCount++;
            }
        });

        $targetDepartment = Department::find($targetDepartmentId);

        return redirect()->route('admin.results.index')
            ->with(
                'success',
                "{$copiedCount} result(s) copied to {$targetDepartment?->name}. {$skippedCount} duplicate result(s) were skipped."
            );
    }

    /**
     * Remove the specified result from storage.
     */
    public function destroy(Request $request, Result $result)
    {
        $this->ensureCanManageResultRecord($result);
        $userId = $request->input('user_id') ?? $result->user_id;
        $session = $request->input('session') ?? $result->session;
        $semester = $request->input('semester') ?? $result->semester;
        $departmentId = $request->input('department_id') ?? $result->department_id;

        $result->delete();

        return redirect()->route('admin.results.editGroup', [
            $userId,
            $session,
            $semester,
            'department_id' => $departmentId,
        ])
            ->with('success', 'Result deleted successfully.');
    }

    public function upload()
    {
        $actor = Auth::user();
        $courses = $this->getAccessibleCoursesForUser($actor);
        $departmentIds = $courses->pluck('department_id')->unique()->values();
        $students = User::where('usertype', 'student')
            ->when(!$this->isPrivilegedResultManager($actor), fn ($query) => $query->whereIn('department_id', $departmentIds))
            ->get();

        return view('admin.result.upload', compact('students', 'courses'));
    }

    public function storeUpload(Request $request)
    {
        $actor = Auth::user();
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'file' => 'required|file|mimes:csv,xlsx|max:2048',
        ]);

        $course = $this->resolveManagedCourse($request->course_id);

        Excel::import(new ResultsImport($request->user_id, $course, $actor->id), $request->file('file'));

        return redirect()->route('admin.results.index')
                         ->with('success', 'Results uploaded successfully. You can now generate the transcript.');
    }

    public function generateTranscriptForSemester(Request $request, $userId, $session, $semester)
    {
        $user = User::findOrFail($userId);
        $session = urldecode($session);
        $departmentId = $this->resolveDepartmentId($request, $user);

        // fetch all results for that user/session/semester
        $results = Result::where('user_id', $userId)
                        ->where('session', $session)
                        ->where('semester', $semester)
                        ->where('department_id', $departmentId)
                        ->get();

        if ($results->isEmpty()) {
            return back()->with('error', 'No results found for this session and semester.');
        }

        $this->generateTranscript($user, $results, $session, $semester, $departmentId);

        return back()->with('success', 'Transcript generated successfully.');
    }

    public function generateFullTranscriptForStudent(Request $request, $userId)
    {
        if (!$this->isPrivilegedResultManager(Auth::user())) {
            abort(403, 'Unauthorized');
        }

        $user = User::findOrFail($userId);
        $departmentId = $this->resolveDepartmentId($request, $user);

        $results = Result::where('user_id', $userId)
            ->where('department_id', $departmentId)
            ->get();

        if ($results->isEmpty()) {
            return back()->with('error', 'No results found for this student in the selected department.');
        }

        $this->generateFullTranscript($user, $departmentId);

        return back()->with('success', 'Full transcript generated successfully.');
    }

    protected function generateTranscript(User $user, $results, $session, $semester, $departmentId = null)
    {
        $departmentId = $departmentId ?? $user->department_id;
        $department = Department::find($departmentId);

        // GPA for this semester
        $totalCreditUnits = $results->sum('credit_unit');
        $weightedSum = $results->sum(fn($res) => $res->credit_unit * $res->grade_point);
        $gpa = $totalCreditUnits > 0 ? round($weightedSum / $totalCreditUnits, 2) : 0;

        // CGPA across all results
        $allResults = Result::where('user_id', $user->id)
            ->where('department_id', $departmentId)
            ->get();
        $totalAllCreditUnits = $allResults->sum('credit_unit');
        $totalWeightedSum = $allResults->sum(fn($res) => $res->credit_unit * $res->grade_point);
        $cgpa = $totalAllCreditUnits > 0 ? round($totalWeightedSum / $totalAllCreditUnits, 2) : null;

        // sanitize session & semester for filename
        $sanitizedSession = str_replace(['/', ' ', '\\'], '_', $session);
        $sanitizedSemester = str_replace(['/', ' ', '\\'], '_', $semester);

        // build PDF
        $pdf = Pdf::loadView('documents.transcript', [
            'student'    => $user,
            'results'    => $results,
            'totalCreditUnits' => $totalCreditUnits,
            'gpa'        => $gpa,
            'cgpa'       => $cgpa,
            'department' => $department,
            'session'    => $session,
            'semester'   => $semester,
        ]);

        $transcriptName = "transcript_{$user->id}_{$sanitizedSession}_{$sanitizedSemester}_" . time() . '.pdf';
        $relativePath   = 'documents/transcripts/' . $transcriptName;

        // save file
        Storage::disk('public')->put($relativePath, $pdf->output());

        // ✅ store the public URL in DB
        $transcriptUrl = Storage::url($relativePath);

        Result::where('user_id', $user->id)
            ->where('session', $session)
            ->where('semester', $semester)
            ->where('department_id', $departmentId)
            ->update(['transcript_path' => $transcriptUrl]);

        return $transcriptUrl;
    }


    protected function generateFullTranscript(User $user, $departmentId = null)
    {
        $departmentId = $departmentId ?? $user->department_id;

        // Load all results grouped by session and semester
        $allResults = Result::where('user_id', $user->id)
                            ->where('department_id', $departmentId)
                            ->orderBy('session')
                            ->orderByRaw("FIELD(semester, 'First', 'Second')")
                            ->get()
                            ->groupBy(fn($result) => $result->session . '_' . $result->semester);

        $department = Department::find($departmentId);

        // Build array with GPA per semester
        $transcriptData = [];
        foreach ($allResults as $key => $results) {
            $results = collect($results);
            $totalCreditUnits = $results->sum('credit_unit');
            $weightedSum = $results->sum(fn($res) => $res->credit_unit * $res->grade_point);
            $gpa = $totalCreditUnits > 0 ? round($weightedSum / $totalCreditUnits, 2) : 0;

            $transcriptData[$key] = [
                'results' => $results,
                'totalCreditUnits' => $totalCreditUnits,
                'gpa' => $gpa,
            ];
        }

        // CGPA across all results
        $totalAllCreditUnits = $allResults->flatten()->sum('credit_unit');
        $totalWeightedSum = $allResults->flatten()->sum(fn($res) => $res->credit_unit * $res->grade_point);
        $cgpa = $totalAllCreditUnits > 0 ? round($totalWeightedSum / $totalAllCreditUnits, 2) : null;

        // build PDF
        $pdf = Pdf::loadView('documents.full_transcript', [
            'student'       => $user,
            'transcriptData'=> $transcriptData,
            'cgpa'          => $cgpa,
            'department'    => $department,
        ]);

        // ✅ save to storage
        $transcriptName = "full_transcript_{$user->id}_" . time() . '.pdf';
        $relativePath   = 'documents/transcripts/' . $transcriptName;
        Storage::disk('public')->put($relativePath, $pdf->output());

        // ✅ store the public URL
        $transcriptUrl = Storage::url($relativePath);

        Result::where('user_id', $user->id)
            ->where('department_id', $departmentId)
            ->update(['full_transcript_path' => $transcriptUrl]);

        return $transcriptUrl;
    }

    protected function resolveResultScore($score = null, $caScore = null, $examScore = null)
    {
        $resolvedScore = Result::resolveScore($score, $caScore, $examScore);

        if ($resolvedScore === null) {
            throw ValidationException::withMessages([
                'score' => 'Provide either a score or CA/Exam values.',
            ]);
        }

        if ($resolvedScore > 100) {
            throw ValidationException::withMessages([
                'score' => 'The combined CA and Exam score cannot exceed 100.',
            ]);
        }

        return $resolvedScore;
    }

    protected function isPrivilegedResultManager(User $user)
    {
        return in_array($user->usertype, ['admin', 'exam_officer'], true);
    }

    protected function getAccessibleCoursesForUser(User $user)
    {
        if ($this->isPrivilegedResultManager($user)) {
            return Courses::with('department')->orderBy('code')->get();
        }

        if ($user->usertype === 'lecturer') {
            return $user->assignedCourses()->with('department')->orderBy('code')->get();
        }

        return collect();
    }

    protected function applyAccessibleCourseScope($query, User $user)
    {
        if ($this->isPrivilegedResultManager($user) || $user->usertype !== 'lecturer') {
            return $query;
        }

        $courses = $this->getAccessibleCoursesForUser($user);

        if ($courses->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($courseQuery) use ($courses) {
            foreach ($courses as $course) {
                $courseQuery->orWhere(function ($matchQuery) use ($course) {
                    $matchQuery->where('department_id', $course->department_id)
                        ->where('course_code', $course->code);
                });
            }
        });
    }

    protected function resolveManagedCourse($courseId)
    {
        $course = Courses::with('department')->findOrFail($courseId);
        $actor = Auth::user();

        if ($actor->usertype === 'lecturer' && !$actor->assignedCourses()->where('courses.id', $course->id)->exists()) {
            abort(403, 'You are not assigned to this course.');
        }

        return $course;
    }

    protected function ensureCourseCodeIsManageable($courseCode, $departmentId)
    {
        $actor = Auth::user();

        if ($this->isPrivilegedResultManager($actor) || $actor->usertype !== 'lecturer') {
            return;
        }

        $isAssigned = $actor->assignedCourses()
            ->where('code', $courseCode)
            ->where('department_id', $departmentId)
            ->exists();

        if (!$isAssigned) {
            abort(403, 'You are not assigned to this course.');
        }
    }

    protected function ensureCanManageResultRecord(Result $result)
    {
        $this->ensureCourseCodeIsManageable($result->course_code, $result->department_id);
    }

    protected function resolveDepartmentId(Request $request, User $user)
    {
        return (int) ($request->input('department_id') ?: $user->department_id);
    }


}
