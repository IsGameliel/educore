<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $staffs = User::whereNot('usertype', 'student')->paginate(10);
        return view('admin.staffs.index', compact('staffs'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.staffs.create');
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
            'usertype' => ['required', 'string'], // Ensures valid usertype
        ]);

        // Transaction to store the user
        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'usertype' => $request->usertype,
            ]);

            // Create default team for Jetstream (if necessary)
            $this->createTeam($user);
        });

        // Redirect back to the index page with a success message
        return redirect()
            ->route('admin.staffs.index')
            ->with('success', 'Staff created successfully.');
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
        $staff = User::findOrFail($id);
        return view('admin.staffs.edit', compact('staff'));
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
            'usertype' => ['required', 'string'], // Ensures valid usertype
        ]);

        // Retrieve the student by ID
        $staff = User::findOrFail($id);

        // Update the student's details
        $staff->update([
            'name' => $request->name,
            'email' => $request->email,
            'usertype' => $request->usertype,
        ]);

        // If a new password is provided, update it
        if ($request->filled('password')) {
            $staff->update([
                'password' => Hash::make($request->password),
            ]);
        }

        // Redirect back with success message
        return redirect()
            ->route('admin.staffs.index')
            ->with('success', 'Staff updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $staff)
    {
        $staff->delete();
        return redirect()->route('admin.staffs.index')->with('success', 'Staff deleted successfully');
    }
}
