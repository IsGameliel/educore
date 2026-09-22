<?php

namespace App\Http\Controllers;

use App\Services\EmailVerificationOtpService;
use Illuminate\Http\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EmailOtpController extends Controller
{
    public function show(Request $request)
    {
        return ($request->user()->isStaff() || $request->user()->hasVerifiedEmail())
            ? redirect()->route('dashboard')
            : view('auth.verify-email');
    }

    public function verify(Request $request, EmailVerificationOtpService $otp)
    {
        if (($request->user()->isStaff() || $request->user()->hasVerifiedEmail())) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate(['code' => ['required', 'string', 'regex:/^[0-9]{6}$/']]);
        if (! $otp->verify($request->user(), $validated['code'])) {
            return back()->withErrors(['code' => 'This code is incorrect, expired, or has reached its attempt limit. Check your latest email or request a new code.']);
        }

        return redirect()->route('dashboard');
    }

    public function resend(Request $request, EmailVerificationOtpService $otp)
    {
        if (($request->user()->isStaff() || $request->user()->hasVerifiedEmail())) {
            return redirect()->route('dashboard');
        }

        try {
            if (! $otp->send($request->user())) {
                return back()->withErrors(['code' => 'Please wait 60 seconds between code requests.']);
            }
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return back()->withErrors(['code' => 'We could not send your code. Please try again shortly.']);
        }

        return back()->with('status', 'verification-code-sent');
    }
}
