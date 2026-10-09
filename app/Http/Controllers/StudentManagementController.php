<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use App\Imports\StudentImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Exports\StudentsExport;
use App\Support\AccountCredentialMailer;
use Maatwebsite\Excel\Facades\Excel;




class StudentManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        [$query, $filters] = $this->filteredStudents($request);

        if ($request->filled('export') && $request->export === 'excel') {
            $collection = $query->get(); // get filtered collection
            return Excel::download(new StudentsExport($collection), 'students.xlsx');
        }

        $students = $query->paginate(15)->withQueryString();

        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('admin.students.index', compact('students', 'departments', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    private function filteredStudents(Request $request): array
    {
        $filters = $request->validate([
            'name' => 'nullable|string|max:255',
            'department' => 'nullable|integer|exists:departments,id',
            'level' => 'nullable|in:100,200,300,400,500,600',
        ]);
        $query = User::where('usertype', 'student')
            ->when($filters['name'] ?? null, fn ($q, $value) => $q->where('name', 'like', "%{$value}%"))
            ->when($filters['department'] ?? null, fn ($q, $value) => $q->where('department_id', $value))
            ->when($filters['level'] ?? null, fn ($q, $value) => $q->where('level', $value));
        return [$query, $filters];
    }

    public function bulkEntryYear(Request $request)
    {
        $data = $request->validate(['entry_year' => 'required|integer|between:1900,2100']);
        [$query, $filters] = $this->filteredStudents($request);
        $count = DB::transaction(function () use ($query, $data, $request, $filters) {
            $count = 0;
            foreach ($query->lockForUpdate()->lazyById(200) as $student) {
                $before = $student->entry_year;
                if ((int) $before === (int) $data['entry_year']) { continue; }
                $student->update(['entry_year' => $data['entry_year']]);
                \App\Support\ActivityLogger::log($request->user(), 'student_entry_year_updated', 'Updated student entry year in bulk.', [
                    'subject' => $student, 'target_user' => $student,
                    'properties' => ['before' => $before, 'after' => (int) $data['entry_year'], 'filters' => $filters],
                ]);
                $count++;
            }
            return $count;
        });
        return redirect()->route('admin.students.index', array_filter($filters, fn ($value) => filled($value)))
            ->with('success', "Entry year updated to {$data['entry_year']} for {$count} student(s).");
    }

    public function create()
    {
        $departments = Department::all();
        return view('admin.students.create', compact('departments'));
    }

    public function showImportForm()
    {
        return view('admin.students.import');
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
            'level' => ['required', 'in:100,200,300,400,500,600'], // Ensures valid levels
            'department_id' => ['required', 'exists:departments,id'], // Validates department ID exists in DB
            'matric_number' => ['nullable', 'string', 'max:255', 'unique:users,matric_number'],
            'entry_year' => ['required', 'integer', 'between:1900,2100'],
        ]);

        $plainPassword = $request->password;
        $createdStudent = null;

        // Transaction to store the user
        DB::transaction(function () use ($request, &$createdStudent) {
            $role = 'student'; // Assign default role as student

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'usertype' => $role,
                'matric_number' => $request->matric_number,
                'level' => $request->level, // Save level
                'department_id' => $request->department_id, // Save department
                'entry_year' => $request->input('entry_year'),
            ]);

            // Create default team for Jetstream (if necessary)
            $this->createTeam($user);

            $createdStudent = $user;
        });

        if ($createdStudent) {
            AccountCredentialMailer::send($createdStudent, $plainPassword, 'created');
        }

        // Redirect back to the index page with a success message
        return redirect()
            ->route('admin.students.index')
            ->with('success', 'Student created successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new StudentImport();

        Excel::import($import, $request->file('file'));

        $message = "{$import->createdCount()} student account(s) created successfully.";

        if ($import->emailedCount() > 0) {
            $message .= " {$import->emailedCount()} welcome email(s) queued.";
        }

        if ($import->failedRows()) {
            return redirect()
                ->route('admin.students.import.form')
                ->with('warning', $message)
                ->with('import_errors', $import->failedRows());
        }

        return redirect()
            ->route('admin.students.index')
            ->with('success', $message);
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
            'level' => ['required', 'in:100,200,300,400,500,600'],
            'department_id' => ['required', 'exists:departments,id'],
            'matric_number' => ['nullable', 'string', 'max:255', Rule::unique('users', 'matric_number')->ignore($id)],
            'entry_year' => ['required', 'integer', 'between:1900,2100'],
        ]);

        // Retrieve the student by ID
        $student = User::findOrFail($id);

        $plainPassword = $request->filled('password') ? $request->password : null;

        // Update basic details
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'matric_number' => $request->matric_number,
            'level' => $request->level,
            'department_id' => $request->department_id,
        ];
        if ($request->has('entry_year')) {
            $updateData['entry_year'] = $request->input('entry_year');
        }

        // If a new password is provided, add it
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // Perform the update and verify it succeeded
        try {
            DB::transaction(function () use ($student, $updateData) {
                $student->update($updateData);
                // Force refresh from database to ensure update was persisted
                $student->refresh();
            });
        } catch (\Exception $e) {
            \Log::error('Error updating student: ' . $e->getMessage(), [
                'student_id' => $id,
                'data' => $updateData
            ]);
            
            return redirect()
                ->route('admin.students.index')
                ->with('error', 'Failed to update student: ' . $e->getMessage());
        }

        AccountCredentialMailer::send($student, $plainPassword, 'updated');

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
        return redirect()->route('admin.students.index')->with('success', 'Student deactivated. Academic and financial records have been retained.');
    }
}
