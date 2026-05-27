<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;


class CustomProfileController extends Controller
{
    public function show(Request $request)
    {
        // Fetch all departments
        $departments = Department::all();

        return view('profile.show', [
            'request' => $request,
            'user' => $request->user(),
            'departments' => $departments, // Pass departments to the view
        ]);
    }
}
