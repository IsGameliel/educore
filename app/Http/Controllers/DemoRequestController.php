<?php

namespace App\Http\Controllers;

use App\Mail\DemoRequestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class DemoRequestController extends Controller
{
    public const TOPICS = [
        'registration' => 'Student records and course registration',
        'results' => 'Results, grading and publication',
        'broadsheets' => 'Broadsheets and academic reports',
        'payments' => 'Tuition invoices and payments',
        'teaching' => 'Course materials, schedules and tests',
        'setup' => 'School setup and onboarding',
    ];

    public function show()
    {
        return view('request-demo', ['demoTopics' => self::TOPICS]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email:rfc|max:254',
            'institution' => 'required|string|max:200',
            'role' => ['required', Rule::in(['Administrator', 'Registrar', 'Exam officer', 'Finance officer', 'Department head', 'IT officer', 'Lecturer', 'Other'])],
            'student_count' => ['nullable', Rule::in(['Under 500', '500-1999', '2000-4999', '5000+', 'Not sure yet'])],
            'topics' => 'nullable|array|max:6',
            'topics.*' => ['string', 'distinct', Rule::in(array_keys(self::TOPICS))],
            'message' => 'nullable|string|max:2000',
            'consent' => 'accepted',
        ]);
        $data['topics'] = array_map(fn ($topic) => self::TOPICS[$topic], $data['topics'] ?? []);

        try {
            Mail::to(config('euvion.demo_email'))->send(new DemoRequestMail($data));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return redirect()->route('demo.show')->withInput()->withErrors([
                'delivery' => 'Your request could not be sent. Please try again or email '.config('euvion.demo_email').' directly.',
            ]);
        }

        return redirect()->route('demo.show')->with('demo_sent', true);
    }
}
