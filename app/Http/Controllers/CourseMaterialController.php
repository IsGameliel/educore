<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CourseMaterial;
use Illuminate\Support\Facades\Storage;
use App\Models\Department;
use App\Models\Courses;


class CourseMaterialController extends Controller
{
    /**
     * Display a listing of the course materials.
     */
    public function index()
    {
        $courseMaterials = CourseMaterial::paginate(10);
        return view('admin.courses.materials.index', compact('courseMaterials'));
    }

    public function create()
    {
        $departments = Department::all();
        $courses = Courses::all();
        return view('admin.courses.materials.create', compact('departments', 'courses'));
    }

    /**
     * Store a newly created course material.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'level' => 'required|string',
            'semester' => 'required|string',
            'department_id' => 'nullable|exists:departments,id',
            'course_id' => 'nullable|exists:courses,id',
            'file' => 'required|file|mimes:pdf|max:20480', // Validate for PDF files
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5048', // Validate for images
        ]);

        // Store the uploaded PDF
        $filePath = $request->file('file')->store('course_materials', 'public');

        // Store the uploaded cover photo if provided
        $coverPhotoPath = $request->hasFile('cover_photo')
            ? $request->file('cover_photo')->store('course_material_covers', 'public')
            : null;

        // Create a new course material record
        $courseMaterial = CourseMaterial::create([
            'title' => $request->title,
            'level' => $request->level,
            'semester' => $request->semester,
            'department_id' => $request->department_id,
            'course_id' => $request->course_id,
            'file_path' => $filePath,
            'cover_photo' => $coverPhotoPath,
        ]);

        return redirect('admin/course-materials');
    }

    /**
     * Display the specified course material.
     */

     /**
 * Show the form for editing the specified course material.
 */
    public function edit($id)
    {
        $courseMaterial = CourseMaterial::findOrFail($id);
        $departments = Department::all();
        $courses = Courses::all();
        return view('admin.courses.materials.edit', compact('courseMaterial', 'departments', 'courses'));
    }

/**
 * Update the specified course material in storage.
 */
    public function update(Request $request, $id)
    {
        $courseMaterial = CourseMaterial::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'level' => 'required|string',
            'semester' => 'required|string',
            'department_id' => 'nullable|exists:departments,id',
            'course_id' => 'nullable|exists:courses,id',
            'file' => 'nullable|file|mimes:pdf|max:20480', // Validate for PDF files
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5048', // Validate for images
        ]);

        // Update the PDF file if provided
        if ($request->hasFile('file')) {
            // Delete the old file
            Storage::disk('public')->delete($courseMaterial->file_path);
            $filePath = $request->file('file')->store('course_materials', 'public');
            $courseMaterial->file_path = $filePath;
        }

        // Update the cover photo if provided
        if ($request->hasFile('cover_photo')) {
            // Delete the old cover photo
            Storage::disk('public')->delete($courseMaterial->cover_photo);
            $coverPhotoPath = $request->file('cover_photo')->store('course_material_covers', 'public');
            $courseMaterial->cover_photo = $coverPhotoPath;
        }

        // Update other fields
        $courseMaterial->update([
            'title' => $request->title,
            'level' => $request->level,
            'semester' => $request->semester,
            'department_id' => $request->department_id,
            'course_id' => $request->course_id,
        ]);

        return redirect('admin/course-materials')->with('success', 'Course material updated successfully.');
    }


    public function show($id)
    {
        $courseMaterial = CourseMaterial::findOrFail($id);
        return response()->json($courseMaterial);
    }

    /**
     * Download the course material PDF.
     */
    public function download($id)
    {
        $courseMaterial = CourseMaterial::findOrFail($id);
        return Storage::disk('public')->download($courseMaterial->file_path);
    }

    /**
     * Remove the specified course material.
     */
    public function destroy($id)
    {
        $courseMaterial = CourseMaterial::findOrFail($id);

        // Delete the file from storage
        Storage::disk('public')->delete($courseMaterial->file_path);

        // Delete the record from the database
        $courseMaterial->delete();

        return response()->json(['message' => 'Course material deleted successfully.']);
    }
}
