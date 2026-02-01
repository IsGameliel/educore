<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Responses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PreventTestRetake
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('PreventTestRetake middleware', ['request' => $request->all()]);
        $testId = $request->route('testId');
        $existing = Responses::where('test_id', $testId)
            ->where('student_id', auth()->id())
            ->first();

        if ($existing) {
            Log::info('Test retake prevented', ['testId' => $testId]);
            return redirect()->route('student.tests.index')->with('error', 'You have already taken this test.');
        }

        return $next($request);
    }
}
