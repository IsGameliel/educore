<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\courseMaterial;

class StudentController extends Controller
{
    public function index(){
        return view('student.dashboard');
    }

    public function courseMaterial(){
        $user = Auth::user();
        $courseMaterials = courseMaterial::where('level', $user->level)
                ->where('department_id', $user->department_id)->get();
        return view('student.course.material.index', compact('courseMaterials'));
    }
}
