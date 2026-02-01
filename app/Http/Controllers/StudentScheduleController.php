<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassSchedule;
use App\Models\User;
use App\Models\Courses;

class StudentScheduleController extends Controller
{
    /**
     * Display the student's class schedule.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get the logged-in student
        $student = auth()->user();

        // Check if the student has a department field
        if (!$student->department) {
            return redirect()->back()->with('error', 'No department assigned to the student.');
        }

        // Fetch the class schedules for the student's department
        $schedules = ClassSchedule::with('lecturer')
            ->where('department_id', $student->department->id) // Match the student's department
            ->get()
            ->map(function ($schedule) {
                $course = Courses::find($schedule->subject); // Assuming subject is a course ID
                $schedule->subject = $course ? $course->title : 'Unknown Course'; // Replace ID with title
                return $schedule;
            });

        return view('student.schedule', compact('schedules'));
    }
}
