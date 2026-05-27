<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(){
        $students = User::where('usertype' == 'student')::count()->get();
        return view('admin.dashboard2', compact('students'));
    }
}
