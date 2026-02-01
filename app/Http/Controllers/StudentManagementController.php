<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Exports\StudentsExport;
use Maatwebsite\Excel\Facades\Excel;



class StudentManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $students = User::where('usertype', 'student')->paginate(10);

        $query = User::where('usertype', 'student');

        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }

        if ($request->filled('department')) {
            $query->whereHas('department', function ($q) use ($request) {
                $q->where('name', $request->department);
            });
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('export') && $request->export === 'excel') {
            $collection = $query->get(); // get filtered collection
            return Excel::download(new StudentsExport($collection), 'students.xlsx');
        }

        $students = $query->paginate(15)->withQueryString();

        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('admin.students.index', compact('students', 'departments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departments = Department::all();
        return view('admin.students.create', compact('departments'));
    }

    /**
     * Store a newly created resource in storage.
     */

    protected function createTeam(User $user)
    {
        $user->ownedTeams()->create([
            'name' => $user->name . "'s Team", // Default team name
            'personal_team' => true,
        ]);
    }

    public function store(Request $request)
    {
        // Validate incoming data
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', 'min:8'], // Ensures password matches password_confirmation
            'level' => ['required', 'in:100,200,300,400,500'], // Ensures valid levels
            'department_id' => ['required', 'exists:departments,id'], // Validates department ID exists in DB
        ]);

        // Transaction to store the user
        DB::transaction(function () use ($request) {
            $role = 'student'; // Assign default role as student

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'usertype' => $role,
                'level' => $request->level, // Save level
                'department_id' => $request->department_id, // Save department
            ]);

            // Create default team for Jetstream (if necessary)
            $this->createTeam($user);
        });

        // Redirect back to the index page with a success message
        return redirect()
            ->route('admin.students.index')
            ->with('success', 'Student created successfully.');
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $student = User::findOrFail($id);
        $departments = Department::all();
        return view('admin.students.edit', compact('student', 'departments'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate the request
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $id], // Ensure email is unique except for the current student
            'password' => ['nullable', 'confirmed', 'min:8'], // Password is optional, but must be confirmed if provided
            'level' => ['required', 'in:100,200,300,400,500'],
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        // Retrieve the student by ID
        $student = User::findOrFail($id);

        // Update the student's details
        $student->update([
            'name' => $request->name,
            'email' => $request->email,
            'level' => $request->level,
            'department_id' => $request->department_id,
        ]);

        // If a new password is provided, update it
        if ($request->filled('password')) {
            $student->update([
                'password' => Hash::make($request->password),
            ]);
        }

        // Redirect back with success message
        return redirect()
            ->route('admin.students.index')
            ->with('success', 'Student updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $student)
    {
        $student->delete();
        return redirect()->route('admin.students.index')->with('success', 'Student deleted successfully');
    }
}
