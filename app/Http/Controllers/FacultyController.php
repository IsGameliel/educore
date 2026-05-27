<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Faculty;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\FacultyImport;


class FacultyController extends Controller
{
    public function index()
    {
        // Fetch all faclcuties with their associated departments
        $faculties= Faculty::all(); // Paginated for better UI
        return view('admin.faculties.index', compact('faculties'));
    }

    public function create()
    {
        return view('admin.faculties.create');
    }

    public function store(Request $request)
    {
        // Validate input data
        $request->validate([
            'name' => 'required|string|max:200',
        ]);

        // Create the course
        Faculty::create($request->all());

        return redirect()->route('admin.faculties.index')->with('success', 'Course created successfully!');
    }

    public function edit(Faculty $faculty){
        return view('admin.faculties.edit', compact('faculty'));
    }

    public function update(Request $request, Faculty $faculty){
        try{
            $request->validate([
                'name' => 'required|string|max:200',
                'description' => 'required',
            ]);
        }catch (\Illuminate\Validation\ValidationException $e){
            dd($e->errors());
        }

        $faculty->update($request->all());

        return redirect()->route('admin.faculties.index')->with('success', 'Faculty updated successfully');
    }

    public function destroy(Faculty $faculty){
        $faculty->delete();
        return redirect()->route('admin.faculties.index')->with('success', 'Faculty deleted successfully');
    }

    public function showImportForm()
    {
        return view('admin.faculties.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new FacultyImport, $request->file('file'));

        return redirect()->route('admin.faculties.index')->with('success', 'Faculties imported successfully!');
    }
}
