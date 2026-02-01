<?php

namespace App\Http\Controllers;

use App\Models\CourseRegistration;
use App\Models\Courses;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Department;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CoursesExport;
use PDF;


class CourseRegistrationController extends Controller
{
    // Maximum allowed credit units per semester
    private $creditUnitLimits = [
        '100' => 24, // Max 24 credit units for 100-level
        '200' => 24, // Max 30 credit units for 200-level
        '300' => 24, // Max 30 credit units for 300-level
        '400' => 24, // Max 30 credit units for 400-level
    ];

    /**
     * Show the course registration form.
     */
    public function showRegistrationForm()
    {
        $user = Auth::user(); // Get the authenticated student
        $departmentId = $user->department_id;
        $courses = Courses::where('department_id', $departmentId)->where('level', $user->level)->get();
        $departments = Department::all();

        return view('student.coursereg.create', compact('courses', 'departments'));
    }

    public function getCoursesByLevel(Request $request)
    {
        $user = Auth::user();
        $departmentId = $user->department_id;
        $level = $request->level;
        $semester = $request->semester;

        $courses = Courses::where('department_id', $departmentId)
            ->where('level', $level)
            ->where('semester', $semester)
            ->with('prerequisites')
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
        $semester = $request->input('semester');
        $level = $request->input('level');
        $courseIds = $request->input('course_ids');

        // Validate that the courses exist
        $courses = Courses::whereIn('id', $courseIds)->get();
        if ($courses->count() != count($courseIds)) {
            return response()->json(['error' => 'One or more courses do not exist.'], 400);
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
                        return response()->json([
                            'error' => 'You must complete all prerequisite courses before registering for: ' . $course->title
                        ], 400);
                    }
                }
            }
        }

        // Step 2: Get the total credit units already registered for this semester
        $totalCreditUnits = CourseRegistration::getTotalCreditUnitsForSemester($userId, $semester);

        // Step 3: Check if the total credit units exceed the allowed limit for the student level
        $creditUnitLimit = $this->getCreditUnitLimitForLevel($level);
        $totalCourseCredits = $courses->sum('credit_unit');

        if (($totalCreditUnits + $totalCourseCredits) > $creditUnitLimit) {
            return response()->json([
                'error' => "Credit unit limit exceeded for this semester. Maximum allowed is $creditUnitLimit."
            ], 400);
        }

        // Step 4: Proceed with the bulk course registration
        foreach ($courses as $course) {
            // Create a new registration for each course
            CourseRegistration::create([
                'user_id' => $userId,
                'course_id' => $course->id,
                'semester' => $semester, // Save as "First Semester" or "Second Semester"
                'status' => 'registered', // You can add more status options like 'pending', 'approved', etc.
            ]);
        }

        return redirect()->route('student.courses.registered', ['semester' => $semester])
        ->with('success', 'Courses successfully registered!');

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

    public function getRegisteredCourses($semester)
    {
        $userId = Auth::id();

        // Normalize Semester
        if ($semester == 1 || strtolower($semester) == "1") {
            $semester = "First";
        } elseif ($semester == 2 || strtolower($semester) == "2") {
            $semester = "Second";
        }

        // If still not valid, default to First
        if (!in_array($semester, ["First", "Second"])) {
            $semester = "First";
        }

        $courses = CourseRegistration::with('course')
            ->where('user_id', $userId)
            ->where('semester', $semester)
            ->get();

        return view('student.coursereg.index', compact('courses', 'semester'));
    }



    /**
     * Withdraw a student from a registered course.
     */
    public function withdrawFromCourse(Request $request)
    {
        $userId = Auth::user()->id;
        $courseId = $request->input('course_id');

        // Check if the student is registered for the course
        $registration = CourseRegistration::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        if (!$registration) {
            return response()->json(['error' => 'You are not registered for this course.'], 400);
        }

        // Remove the registration (i.e., withdrawal)
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
        $semester = $request->input('semester');

        // Check if the student is already registered for the course
        $existingRegistration = CourseRegistration::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('semester', $semester)
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
        $semester = $request->input('semester'); // Get the semester from the request

        // Fetch the courses registered by the user for the given semester
        $courses = CourseRegistration::with('course')
            ->where('user_id', $user->id)
            ->whereHas('course', function ($query) use ($semester) {
                $query->where('semester', $semester);
            })
            ->get();

        // Check if courses were fetched
        if ($courses->isEmpty()) {
            return response()->json(['error' => 'No courses found for the selected semester.'], 404);
        }

        // Prepare data for the PDF
        $pdfData = [
            'user' => $user,
            'semester' => ucfirst($semester), // Capitalize "first semester" or "second semester"
            'courses' => $courses,
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
        $semester = $this->getSemesterName($request->input('semester'));

        $courses = CourseRegistration::with('course')
            ->where('user_id', $userId)
            ->where('semester', $semester)
            ->get();

        return Excel::download(new CoursesExport($courses), "registered_courses_{$semester}.xlsx");
    }
}
