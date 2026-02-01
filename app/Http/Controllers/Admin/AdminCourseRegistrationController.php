<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseRegistration;
use App\Models\Courses;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCourseRegistrationController extends Controller
{
    private $creditUnitLimits = [
        '100' => 24,
        '200' => 24,
        '300' => 24,
        '400' => 24,
    ];

    private function normalizeSemester($semester)
    {
        $semester = strtolower(trim((string) $semester));

        if (in_array($semester, ['1', 'first', 'first semester'])) return 'First';
        if (in_array($semester, ['2', 'second', 'second semester'])) return 'Second';

        return 'First';
    }

    private function getCreditUnitLimitForLevel($level)
    {
        return $this->creditUnitLimits[$level] ?? 30;
    }

    // 1) List students (simple)
    public function index(Request $request)
    {
        $students = User::query()
            ->where('usertype', 'student')
            ->when($request->filled('department_id'), fn($q) => $q->where('department_id', $request->department_id))
            ->orderBy('name')
            ->paginate(20);

        return view('admin.course_registrations.index', compact('students'));
    }

    // 2) Show a student's registered courses for a semester
    public function show(User $student, Request $request)
    {
        $semester = $this->normalizeSemester($request->query('semester', 'First'));

        $registrations = CourseRegistration::with('course')
            ->where('user_id', $student->id)
            ->where('semester', $semester)
            ->get();

        $totalCredits = $registrations->sum(fn($r) => $r->course?->credit_unit ?? 0);

        return view('admin.course_registrations.show', compact('student', 'semester', 'registrations', 'totalCredits'));
    }


    public function edit(User $student, Request $request)
    {
        $semester = $this->normalizeSemester($request->query('semester', 'First'));

        $registeredCourseIds = CourseRegistration::where('user_id', $student->id)
            ->where('semester', $semester)
            ->pluck('course_id')
            ->toArray();

        // ✅ ADD THIS: current statuses for registered courses
        $registeredStatuses = CourseRegistration::where('user_id', $student->id)
            ->where('semester', $semester)
            ->pluck('status', 'course_id')
            ->toArray();

        $courses = Courses::where('department_id', $student->department_id)
            ->where('level', $student->level)
            ->where('semester', $semester)
            ->with('prerequisites')
            ->orderBy('code')
            ->get();

        return view('admin.course_registrations.edit', compact(
            'student',
            'semester',
            'courses',
            'registeredCourseIds',
            'registeredStatuses'
        ));
    }


    // 4) Update/sync registration
    public function update(User $student, Request $request)
    {
        $semester = $this->normalizeSemester($request->input('semester', 'First'));

        $data = $request->validate([
            'course_ids' => ['array'],
            'course_ids.*' => ['integer'],
            'statuses' => ['array'],
            'statuses.*' => ['in:registered,pending,approved,rejected,withdrawn'],
        ]);

        $courseIds = $data['course_ids'] ?? [];

        // Ensure chosen courses exist and belong to student's dept/level/semester
        $courses = Courses::whereIn('id', $courseIds)
            ->where('department_id', $student->department_id)
            ->where('level', $student->level)
            ->where('semester', $semester)
            ->with('prerequisites')
            ->get();

        if ($courses->count() !== count($courseIds)) {
            return back()->withErrors([
                'course_ids' => 'One or more selected courses are invalid for this student/semester.',
            ]);
        }

        // ---- Rules: prerequisites check ----
        foreach ($courses as $course) {
            foreach ($course->prerequisites as $prereq) {
                $hasPrereq = CourseRegistration::where('user_id', $student->id)
                    ->where('course_id', $prereq->id)
                    ->exists();

                if (!$hasPrereq) {
                    return back()->withErrors([
                        'course_ids' => "Missing prerequisite for {$course->title}: {$prereq->title}",
                    ]);
                }
            }
        }

        // ---- Rules: credit limit ----
        $limit = $this->getCreditUnitLimitForLevel($student->level);
        $newTotal = $courses->sum('credit_unit');

        if ($newTotal > $limit) {
            return back()->withErrors([
                'course_ids' => "Credit unit limit exceeded. Max allowed is {$limit}. Selected is {$newTotal}.",
            ]);
        }

        // ✅ IMPORTANT: include $data inside the transaction
        DB::transaction(function () use ($student, $semester, $courseIds, $data) {

            $existing = CourseRegistration::where('user_id', $student->id)
                ->where('semester', $semester)
                ->pluck('course_id')
                ->toArray();

            $toDelete = array_diff($existing, $courseIds);
            $toAdd = array_diff($courseIds, $existing);

            if (!empty($toDelete)) {
                CourseRegistration::where('user_id', $student->id)
                    ->where('semester', $semester)
                    ->whereIn('course_id', $toDelete)
                    ->delete();
            }

            // ✅ Create new registrations
            foreach ($toAdd as $courseId) {
                CourseRegistration::create([
                    'user_id' => $student->id,
                    'course_id' => $courseId,
                    'semester' => $semester,
                    'status' => 'registered', // default
                ]);
            }

            // ✅ NOW update status for all selected courses
            $statuses = $data['statuses'] ?? [];

            foreach ($courseIds as $courseId) {
                $status = $statuses[$courseId] ?? 'registered';

                CourseRegistration::where('user_id', $student->id)
                    ->where('semester', $semester)
                    ->where('course_id', $courseId)
                    ->update(['status' => $status]);
            }
        });

        return redirect()
            ->route('admin.course-registrations.show', $student->id)
            ->with('success', "Registration updated for {$student->name} ({$semester} Semester).");
    }
}