<?php

namespace App\Http\Controllers;

use App\Imports\ResultsImport;
use App\Models\Result;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $query = Result::with(['user.department']);

        if (Auth::user()->usertype === 'student') {
            $query->where('user_id', Auth::id());
        }

        if ($request->filled('user_id') && Auth::user()->usertype !== 'student') {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('department_id')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
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
        $view = Auth::user()->usertype === 'student' ? 'student.result.index' : 'admin.result.index';

        return view($view, compact('results', 'students', 'departments'));
    }

    public function export(Request $request)
    {
        $query = Result::with('user');

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('department_id') && $request->department_id) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
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
                'Department'     => $result->user->department->name ?? '',
                'Session'        => $result->session,
                'Semester'       => $result->semester,
                'Level'          => $result->level,
                'Course Code'    => $result->course_code,
                'Course Title'   => $result->course_title,
                'Score'          => $result->score,
                'Grade'          => $result->grade,
            ];
        });

        return Excel::download(new \App\Exports\ArrayExport($exportData->toArray()), 'filtered_results.xlsx');
    }

    public function show($userId, $session, $semester)
    {
        $user = User::findOrFail($userId);
        $session = urldecode($session);

        if (Auth::user()->usertype === 'student' && Auth::id() !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $results = Result::bySessionAndSemester($session, $semester)
                        ->where('user_id', $userId)
                        ->get();

        if ($results->isEmpty()) {
            return redirect()->route('student.results.index')->with('error', 'No results found.');
        }

        $totalCreditUnits = $results->sum('credit_unit');
        $weightedSum = $results->sum(fn($result) => $result->credit_unit * $result->grade_point);
        $gpa = $totalCreditUnits > 0 ? round($weightedSum / $totalCreditUnits, 2) : 0;

        $allResults = Result::where('user_id', $userId)->get();
        $totalAllCreditUnits = $allResults->sum('credit_unit');
        $totalWeightedSum = $allResults->sum(fn($result) => $result->credit_unit * $result->grade_point);
        $cgpa = $totalAllCreditUnits > 0 ? round($totalWeightedSum / $totalAllCreditUnits, 2) : null;

        return view('student.result.show', compact(
            'user', 'results', 'totalCreditUnits', 'gpa', 'cgpa', 'session', 'semester'
        ));
    }

    public function create()
    {
        $students = User::where('usertype', 'student')->get();
        $departments = Department::all();
        return view('admin.result.create', compact('students', 'departments'));
    }

    public function editGroup($user_id, $session, $semester)
    {
        $results = Result::where('user_id', $user_id)
            ->where('session', $session)
            ->where('semester', $semester)
            ->get();

        $students = User::where('usertype', 'student')->get();
        $departments = Department::all();

        return view('admin.result.edit-group', compact('results', 'students', 'departments', 'user_id', 'session', 'semester'));
    }

    public function getStudentsByDepartment($department_id)
    {
        $students = User::where('usertype', 'student')
            ->where('department_id', $department_id)
            ->get(['id', 'name', 'matric_number', 'level']);

        return response()->json($students);
    }


    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'session' => 'required|string|max:255',
            'semester' => 'required|in:First,Second',
            'course_code' => 'required|string|max:255',
            'course_title' => 'required|string|max:255',
            'credit_unit' => 'required|integer|min:1',
            'score' => 'required|numeric|min:0|max:100',
            'department_id' => 'required|numeric|min:0',
        ]);

        $user = User::where('usertype', 'student')->findOrFail($request->user_id);
        $gradeData = Result::calculateGradeAndPoint($request->score);

        Result::create([
            'user_id' => $request->user_id,
            'matric_number' => $user->matric_number,
            'session' => $request->session,
            'semester' => $request->semester,
            'level' => $user->level,
            'course_code' => $request->course_code,
            'course_title' => $request->course_title,
            'credit_unit' => $request->credit_unit,
            'score' => $request->score,
            'grade' => $gradeData['grade'],
            'grade_point' => $gradeData['grade_point'],
            'department_id' => $request->department_id,
        ]);

        return redirect()->route('admin.results.index')->with('success', 'Result added successfully.');
    }

    public function edit(Result $result)
    {
        $students = User::where('usertype', 'student')->get();
        return view('admin.result.edit', compact('result', 'students'));
    }

    public function update(Request $request, Result $result)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'session' => 'required|string|max:255',
            'semester' => 'required|in:First,Second',
            'course_code' => 'required|string|max:255',
            'course_title' => 'required|string|max:255',
            'credit_unit' => 'required|integer|min:1',
            'score' => 'required|numeric|min:0|max:100',
            'department_id' => 'required|numeric|min:0',
        ]);

        $user = User::where('usertype', 'student')->findOrFail($request->user_id);
        $gradeData = Result::calculateGradeAndPoint($request->score);

        $result->update([
            'user_id' => $request->user_id,
            'matric_number' => $user->matric_number,
            'session' => $request->session,
            'semester' => $request->semester,
            'level' => $user->level,
            'course_code' => $request->course_code,
            'course_title' => $request->course_title,
            'credit_unit' => $request->credit_unit,
            'score' => $request->score,
            'grade' => $gradeData['grade'],
            'grade_point' => $gradeData['grade_point'],
            'department_id' => $request->department_id,
        ]);

        return redirect()->route('admin.results.index')->with('success', 'Result updated successfully.');
    }

    public function upload()
    {
        $students = User::where('usertype', 'student')->get();
        return view('admin.result.upload', compact('students'));
    }

    public function storeUpload(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'file' => 'required|file|mimes:csv,xlsx|max:2048',
        ]);

        Excel::import(new ResultsImport($request->user_id), $request->file('file'));

        return redirect()->route('admin.results.index')
                         ->with('success', 'Results uploaded successfully. You can now generate the transcript.');
    }

    public function generateTranscriptForSemester($userId, $session, $semester)
    {
        $user = User::findOrFail($userId);
        $session = urldecode($session);

        // fetch all results for that user/session/semester
        $results = Result::where('user_id', $userId)
                        ->where('session', $session)
                        ->where('semester', $semester)
                        ->get();

        if ($results->isEmpty()) {
            return back()->with('error', 'No results found for this session and semester.');
        }

        $this->generateTranscript($user, $results, $session, $semester);

        return back()->with('success', 'Transcript generated successfully.');
    }

    protected function generateTranscript(User $user, $results, $session, $semester)
    {
        $department = Department::find($user->department_id);

        // GPA for this semester
        $totalCreditUnits = $results->sum('credit_unit');
        $weightedSum = $results->sum(fn($res) => $res->credit_unit * $res->grade_point);
        $gpa = $totalCreditUnits > 0 ? round($weightedSum / $totalCreditUnits, 2) : 0;

        // CGPA across all results
        $allResults = Result::where('user_id', $user->id)->get();
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
            ->update(['transcript_path' => $transcriptUrl]);

        return $transcriptUrl;
    }


    protected function generateFullTranscript(User $user)
    {
        // Load all results grouped by session and semester
        $allResults = Result::where('user_id', $user->id)
                            ->orderBy('session')
                            ->orderByRaw("FIELD(semester, 'First', 'Second')")
                            ->get()
                            ->groupBy(fn($result) => $result->session . '_' . $result->semester);

        $department = $user->department;

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
            ->update(['transcript_path' => $transcriptUrl]);

        return $transcriptUrl;
    }


}
