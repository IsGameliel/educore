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

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new DepartmentImport, $request->file('file'));

        return redirect()->route('admin.departments.index')->with('success', 'Departments imported successfully!');
    }
}
