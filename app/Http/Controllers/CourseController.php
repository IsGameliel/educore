<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CourseImport;
use DataTables; // yajra
use App\Exports\CoursesExport;
use App\Models\{
    Department, Courses
};


class CourseController extends Controller
{
    /**
     * Display a listing of the courses.
     */
    public function index(Request $request)
    {
        $departments = Department::orderBy('name')->get(['id','name']);

        $query = Courses::query(); // Use query builder

        if ($request->filled('title')) {
            $query->where('title', 'like', "%{$request->title}%");
        }

        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        $courses = $query->paginate(15)->withQueryString();

        return view('admin.courses.index', compact('courses', 'departments'));
    }




    /**
     * Show the form for creating a new course.
     */
    public function create()
    {
        // Pass departments for dropdown selection in form
        $departments = Department::all();
        return view('admin.courses.create', compact('departments'));
    }

    /**
     * Store a newly created course in storage.
     */
    public function store(Request $request)
    {
        // Validate input data
        $request->validate([
            'code' => 'required|string|max:10',
            'title' => 'required|string|max:255',
            'credit_unit' => 'required|integer|min:1|max:10',
            'semester' => 'required|string|in:First,Second',
            'department_id' => 'required|exists:departments,id',
            'level' => 'required|int|max:535',
        ]);

        // Create the course
        Courses::create($request->all());

        return redirect()->route('admin.courses.index')->with('success', 'Course created successfully!');
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit(Courses $course)
    {
        $departments = Department::all();
        return view('admin.courses.edit', compact('course', 'departments'));
    }

    /**
     * Update the specified course in storage.
     */
    public function update(Request $request, Courses $course)
    {
        try {
            $request->validate([
                'code' => 'required|string|max:10',
                'title' => 'required|string|max:255',
                'credit_unit' => 'required|integer|min:1|max:10',
                'semester' => 'required|string',
                'department_id' => 'required|exists:departments,id',
                'level' => 'required|int|max:225',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            dd($e->errors());
        }

        // Update the course
        $course->update($request->all());

        return redirect()->route('admin.courses.index')->with('success', 'Course updated successfully!');
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

        Excel::import(new CourseImport, $request->file('file'));

        return redirect()->route('admin.courses.index')->with('success', 'Courses imported successfully!');
    }

}
