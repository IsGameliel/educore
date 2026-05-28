<?php

namespace App\Http\Controllers;

use App\Models\CourseRegistration;
use App\Models\Courses;
use App\Models\User;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Department;
use App\Support\ActivityLogger;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CoursesExport;
use PDF;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;


class CourseRegistrationController extends Controller
{
    // Maximum allowed credit units per semester
    private $creditUnitLimits = [
        '100' => 24, // Max 24 credit units for 100-level
        '200' => 24, // Max 30 credit units for 200-level
        '300' => 24, // Max 30 credit units for 300-level
        '400' => 24, // Max 30 credit units for 400-level
    ];

    private function normalizeSemester($semester)
    {
        $semester = strtolower(trim((string) $semester));

        if (in_array($semester, ['1', 'first', 'first semester'])) return 'First';
        if (in_array($semester, ['2', 'second', 'second semester'])) return 'Second';

        // default
        return 'First';
    }


    /**
     * Show the course registration form.
     */
    public function showRegistrationForm()
    {
        $user = Auth::user(); // Get the authenticated student
        $departmentId = $user->department_id;
        $defaultSemester = $this->normalizeSemester(old('semester', 'First'));
        $currentSession = $this->getCurrentAcademicSession();
        $courses = Courses::where('department_id', $departmentId)
            ->forAcademicSession($currentSession)
            ->where('level', $user->level)
            ->where('semester', $defaultSemester)
            ->with('prerequisites')
            ->orderBy('code')
            ->get();
        $departments = Department::all();

        return view('student.coursereg.create', compact('courses', 'departments', 'defaultSemester', 'currentSession'));
    }

    public function getCoursesByLevel(Request $request)
    {
        $user = Auth::user();
        $departmentId = $user->department_id;
        $level = $request->level;
        $semester = $this->normalizeSemester($request->semester);
        $session = $request->query('session', $this->getCurrentAcademicSession());

        $courses = Courses::where('department_id', $departmentId)
            ->forAcademicSession($session)
            ->where('level', $level)
            ->where('semester', $semester)
            ->with('prerequisites')
            ->orderBy('code')
            ->get();

        return response()->json($courses);
    }



    /**
     * Get the credit unit limit based on student's level.
     */
    private function getCreditUnitLimitForLevel($level)
    {
        return $this->creditUnitLimits[$level] ?? 30; // Default to 30 if level is not found
    }

    /**
     * Register a student for multiple courses (bulk registration).
     */
    public function registerForCourses(Request $request)
    {
        $user = Auth::user(); // Get the authenticated student
        $userId = $user->id;
        $semester = $this->normalizeSemester($request->input('semester'));
        $level = $request->input('level');
        $session = $this->getCurrentAcademicSession();
        $courseIds = collect($request->input('course_ids', []))
            ->filter(fn ($courseId) => filled($courseId))
            ->values()
            ->all();

        if (empty($courseIds)) {
            return $this->courseRegistrationError($request, 'Please select at least one course.', 422);
        }

        // Validate that the selected courses belong to the student's department, level, and semester.
        $courses = Courses::whereIn('id', $courseIds)
            ->where('department_id', $user->department_id)
            ->forAcademicSession($session)
            ->where('level', $level)
            ->where('semester', $semester)
            ->get();

        if ($courses->count() != count($courseIds)) {
            return $this->courseRegistrationError(
                $request,
                'One or more selected courses are not available for your level, selected semester, or academic session.',
                422
            );
        }

        $alreadyRegisteredCourse = CourseRegistration::where('user_id', $userId)
            ->where('semester', $semester)
            ->where('session', $session)
            ->whereIn('course_id', $courseIds)
            ->first();

        if ($alreadyRegisteredCourse) {
            return $this->courseRegistrationError(
                $request,
                'One or more selected courses have already been registered for this semester and session.',
                422
            );
        }

        // Step 1: Check if each course has prerequisites
        foreach ($courses as $course) {
            $prerequisiteCourses = $course->prerequisites; // Get the list of prerequisites
            if ($prerequisiteCourses->isNotEmpty()) {
                foreach ($prerequisiteCourses as $prerequisite) {
                    // Check if the student has registered for all prerequisite courses
                    $hasPrerequisite = CourseRegistration::where('user_id', $userId)
                        ->where('course_id', $prerequisite->id)
                        ->exists();

                    if (!$hasPrerequisite) {
                        return $this->courseRegistrationError(
                            $request,
                            'You must complete all prerequisite courses before registering for: ' . $course->title,
                            400
                        );
                    }
                }
            }
        }

        // Step 2: Get the total credit units already registered for this semester
        $totalCreditUnits = CourseRegistration::getTotalCreditUnitsForSemester($userId, $semester, $session);

        // Step 3: Check if the total credit units exceed the allowed limit for the student level
        $creditUnitLimit = $this->getCreditUnitLimitForLevel($level);
        $totalCourseCredits = $courses->sum('credit_unit');

        if (($totalCreditUnits + $totalCourseCredits) > $creditUnitLimit) {
            return $this->courseRegistrationError(
                $request,
                "Course unit exceeded. You can register a maximum of {$creditUnitLimit} units for this semester.",
                400
            );
        }

        // Step 4: Proceed with the bulk course registration
        foreach ($courses as $course) {
            // Create a new registration for each course
            $registration = CourseRegistration::create([
                'user_id' => $userId,
                'acted_by' => $userId,
                'course_id' => $course->id,
                'semester' => $semester, // Save as "First Semester" or "Second Semester"
                'session' => $session,
                'registration_date' => now(),
                'status' => 'registered', // You can add more status options like 'pending', 'approved', etc.
            ]);

            ActivityLogger::log(
                $user,
                'registration_created',
                "{$user->name} registered {$course->code} - {$course->title} for {$semester} Semester",
                [
                    'subject' => $registration,
                    'target_user' => $user,
                    'department_id' => $course->department_id,
                    'properties' => [
                        'course_id' => $course->id,
                        'course_code' => $course->code,
                        'course_title' => $course->title,
                        'semester' => $semester,
                        'session' => $session,
                        'status' => 'registered',
                    ],
                ]
            );
        }

        return redirect()->route('student.courses.registered', ['semester' => $semester, 'session' => $session])
        ->with('success', 'Courses successfully registered!');

    }

    private function courseRegistrationError(Request $request, string $message, int $status = 400): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message], $status);
        }

        return back()
            ->withInput()
            ->withErrors(['course_registration' => $message]);
    }

    /**
     * Convert semester number to "First Semester" or "Second Semester"
     */
    private function getSemesterName($semester)
    {
        return $semester == 1 ? 'First' : 'Second';
    }

    /**
     * Show the list of courses the student is registered for in a specific semester.
     */

    public function getRegisteredCourses(Request $request, $semester)
    {
        $userId = Auth::id();
        $semester = $this->normalizeSemester($request->query('semester', $semester));
        $session = $request->query('session', $this->getCurrentAcademicSession());

        $courses = CourseRegistration::with('course')
            ->where('user_id', $userId)
            ->where('semester', $semester)
            ->where('session', $session)
            ->get();

        $availableSessions = AcademicSession::query()
            ->orderByDesc('start_year')
            ->pluck('name')
            ->merge(
                CourseRegistration::query()
                    ->where('user_id', $userId)
                    ->whereNotNull('session')
                    ->distinct()
                    ->pluck('session')
            )
            ->filter()
            ->unique()
            ->values();

        return view('student.coursereg.index', compact('courses', 'semester', 'session', 'availableSessions'));
    }



    /**
     * Withdraw a student from a registered course.
     */
    public function withdrawFromCourse(Request $request)
    {
        $userId = Auth::user()->id;
        $courseId = $request->input('course_id');
        $semester = $this->normalizeSemester($request->input('semester'));
        $session = $request->input('session', $this->getCurrentAcademicSession());

        // Check if the student is registered for the course
        $registration = CourseRegistration::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('semester', $semester)
            ->where('session', $session)
            ->first();

        if (!$registration) {
            return response()->json(['error' => 'You are not registered for this course.'], 400);
        }

        // Remove the registration (i.e., withdrawal)
        $registration->acted_by = $userId;
        $registration->save();

        $course = $registration->course;
        ActivityLogger::log(
            Auth::user(),
            'registration_withdrawn',
            Auth::user()->name . ' withdrew from ' . ($course?->code ?? 'N/A') . ' - ' . ($course?->title ?? 'Unknown course'),
            [
                'subject' => $registration,
                'target_user_id' => $userId,
                'department_id' => $course?->department_id,
                'properties' => [
                    'course_id' => $course?->id,
                    'course_code' => $course?->code,
                    'course_title' => $course?->title,
                    'semester' => $registration->semester,
                    'status' => 'withdrawn',
                ],
            ]
        );

        $registration->delete();

        return response()->json(['message' => 'Successfully withdrawn from the course.']);
    }

    /**
     * Add a course to the registration queue for the semester.
     */
    public function addCourseToQueue(Request $request)
    {
        $userId = Auth::user()->id;
        $courseId = $request->input('course_id');
        $semester = $this->normalizeSemester($request->input('semester'));
        $session = $request->input('session', $this->getCurrentAcademicSession());

        // Check if the student is already registered for the course
        $existingRegistration = CourseRegistration::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('semester', $semester)
            ->where('session', $session)
            ->first();

        if ($existingRegistration) {
            return response()->json(['error' => 'You are already registered for this course.'], 400);
        }

        // Add the course to the registration queue (or waitlist) if necessary
        // Implement a waitlist/queue system here if needed.

        return response()->json(['message' => 'Course added to registration queue.']);
    }

    /**
     * Generate PDF of registered courses for a specific semester.
     */
    public function downloadCoursesPdf(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user
        $semester = $this->normalizeSemester($request->input('semester'));
        $session = $request->input('session', $this->getCurrentAcademicSession());

        // Fetch the courses registered by the user for the given semester
        $courses = CourseRegistration::with('course')
            ->where('user_id', $user->id)
            ->where('semester', $semester)
            ->where('session', $session)
            ->get();

        // Check if courses were fetched
        if ($courses->isEmpty()) {
            return response()->json(['error' => 'No courses found for the selected semester.'], 404);
        }

        $totalCreditUnits = $courses->sum(fn ($registration) => $registration->course?->credit_unit ?? 0);

        // Prepare data for the PDF
        $pdfData = [
            'user' => $user,
            'semester' => ucfirst($semester), // Capitalize "first semester" or "second semester"
            'session' => $session,
            'courses' => $courses,
            'totalCreditUnits' => $totalCreditUnits,
            'department' => $user->department->name ?? 'N/A', // Assuming a relationship exists
            'level' => $user->level ?? 'N/A', // Replace with the appropriate attribute
        ];

        // Generate the PDF with the courses and user data
        $pdf = PDF::loadView('student.coursereg.pdf', $pdfData);

        // Return the PDF for download
        return $pdf->download("registered_courses_{$semester}.pdf");
    }


    /**
     * Generate Excel of registered courses for a specific semester.
     */
    public function downloadCoursesExcel(Request $request)
    {
        $userId = Auth::id();
        $semester = $this->normalizeSemester($request->input('semester'));
        $session = $request->input('session', $this->getCurrentAcademicSession());

        $courses = CourseRegistration::with('course')
            ->where('user_id', $userId)
            ->where('semester', $semester)
            ->where('session', $session)
            ->get();

        return Excel::download(new CoursesExport($courses), "registered_courses_{$semester}.xlsx");
    }

    private function getCurrentAcademicSession(): string
    {
        $activeAcademicSession = AcademicSession::currentName();

        if ($activeAcademicSession) {
            return $activeAcademicSession;
        }

        $year = (int) now()->format('Y');
        $month = (int) now()->format('n');
        $startYear = $month >= 8 ? $year : $year - 1;

        return $startYear . '/' . ($startYear + 1);
    }


}
