<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DepartmentImport;
use App\Models\{
    Department, Faculty
};

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with('faculty')->paginate(10);
        return view('admin.departments.index', compact('departments'));
    }

    public function create()
    {
        $faculties = Faculty::all(); // Assuming you have a Faculty model
        return view('admin.departments.create', compact('faculties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'faculty_id' => 'required|exists:faculties,id',
            'pass_mark' => 'nullable|integer|min:0|max:100',
        ]);
        Department::create($request->all());

        return redirect()->route('admin.departments.index')->with('success', 'Department created successfully!');
    }

    public function show(Department $department)
    {
        return view('admin.departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        $faculties = Faculty::all();
        return view('admin.departments.edit', compact('department', 'faculties'));
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'faculty_id' => 'required|exists:faculties,id',
            'pass_mark' => 'nullable|integer|min:0|max:100',
        ]);

        $department->update($request->all());

        return redirect()->route('admin.departments.index')->with('success', 'Department updated successfully!');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('admin.departments.index')->with('success', 'Department deleted successfully!');
    }

    public function showImportForm()
    {
        return view('admin.departments.import');
    }

    /**
     * Display a simple page that lists every department alongside its current
     * pass mark.  Administrators can update the values in bulk.
     */
    public function showPassMarks()
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.departments.pass_marks', compact('departments'));
    }

    /**
     * Persist pass mark updates from the settings page.
     */
    public function updatePassMarks(Request $request)
    {
        $data = $request->validate([
            'pass_marks' => 'required|array',
            'pass_marks.*' => 'nullable|integer|min:0|max:100',
        ]);

        foreach ($data['pass_marks'] as $deptId => $mark) {
            $dept = Department::find($deptId);
            if ($dept) {
                $dept->pass_mark = $mark ?? 0;
                $dept->save();
            }
        }

        return redirect()
            ->route('admin.departments.passmarks')
            ->with('success', 'Department pass marks updated successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new DepartmentImport, $request->file('file'));

        return redirect()->route('admin.departments.index')->with('success', 'Departments imported successfully!');
    }
}
