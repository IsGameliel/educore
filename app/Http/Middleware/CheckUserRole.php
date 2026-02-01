<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class weCheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $role = Auth::user()->role;

        // Redirect based on the role
        switch ($role) {
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'student':
                return redirect()->route('student.dashboard');
            case 'lecturer':
                return redirect()->route('lecturer.dashboard');
            case 'vc':
                return redirect()->route('vc.dashboard');
            case 'registrar':
                return redirect()->route('registrar.dashboard');
            case 'bursar':
                return redirect()->route('bursar.dashboard');
            default:
                return $next($request);
        }
    }
}
