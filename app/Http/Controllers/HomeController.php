<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\CourseMaterial;
use App\Models\Courses;
use App\Models\ClassSchedule;

class HomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->usertype == 'student') {
            // Ensure the user has a department assigned
            if (!$user->department) {
                return redirect()->back()->with('error', 'No department assigned to the student.');
            }

            // Fetch course materials and courses
            $courseMaterialsCount = CourseMaterial::where('level', $user->level)
                ->where('department_id', $user->department_id)
                ->count();

            $courseCount = Courses::where('department_id', $user->department_id)
                ->where('level', $user->level)
                ->count();

            // Fetch class schedules
            $schedules = ClassSchedule::with('lecturer')
                ->where('department_id', $user->department->id)
                ->get()
                ->map(function ($schedule) {
                    $course = Courses::find($schedule->subject); // Assuming subject is a course ID
                    $schedule->subject = $course ? $course->title : 'Unknown Course'; // Replace ID with title
                    return $schedule;
                });

            return view('student.dashboard', compact('courseMaterialsCount', 'courseCount', 'schedules'));
        }

        elseif ($user->usertype == 'admin') {
            $studentsCount = User::where('usertype', 'student')->count();
            $departmentsCount = Department::count();
            $facultyCount = Faculty::count();

            return view('admin.dashboard', compact('studentsCount', 'departmentsCount', 'facultyCount'));
        }

        // Default view for other user types
        return redirect('/')->with('error', 'Unauthorized access.');
    }
}
