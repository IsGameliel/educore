<?php

namespace App\Services;

use App\Models\{AcademicSession, AdmissionApplication, CourseRegistration, User};
use App\Services\Academic\AcademicStanding;

class StudentServices
{
    public static function onboarding(User $student): array
    {
        $session = AcademicSession::current();
        $issues = EnrollmentData::issues($student,$session);
        $application = AdmissionApplication::with('payment')->where('user_id',$student->id)->latest()->first();
        $steps = [
            ['label'=>'Verify email','done'=>$student->email_verified_at !== null,'detail'=>'Keep your email address current for portal access.','url'=>route('profile.show')],
            ['label'=>'Confirm enrollment','done'=>!$issues,'detail'=>$issues ? implode('; ',$issues).'. Contact the academic office.' : 'Department, level and entry year are complete.','url'=>route('profile.show')],
            ['label'=>'Matriculation number','done'=>filled($student->matric_number),'detail'=>filled($student->matric_number) ? 'Your matriculation number has been assigned.' : 'Ask the academic office to assign your matriculation number.','url'=>route('profile.show')],
        ];
        if ($application) {
            $steps[] = ['label'=>'Application payment','done'=>$application->payment?->status === 'success',
                'detail'=>$application->payment ? 'Check your application payment confirmation.' : 'No linked payment record. Ask admissions to verify your existing application before making any new payment.',
                'url'=>$application->payment ? route('payments.show',$application->payment) : route('payments.index')];
        }
        if (!$session) {
            $steps[] = ['label'=>'Academic session','done'=>false,'detail'=>'The academic office must activate a session before registration.','url'=>route('academic.assistance')];
        } else {
            foreach (['First','Second'] as $semester) {
                $clearance = app(TuitionBilling::class)->clearance($student,$session,$semester);
                $steps[] = ['label'=>$semester.' semester tuition','done'=>$clearance['cleared'],'detail'=>$clearance['message'],'url'=>route('tuition.index')];
                $registered = CourseRegistration::where('user_id',$student->id)->where('session',$session->name)->where('semester',$semester)->whereIn('status',['registered','approved','completed'])->exists();
                $steps[] = ['label'=>$semester.' semester registration started','done'=>$registered,'detail'=>'Review your required courses and credit limit; having a registration does not confirm a complete course load.','url'=>route('student.courses.registration')];
            }
        }
        return $steps;
    }

    public static function graduation(User $student): ?array
    {
        $session = AcademicSession::current();
        if (!$session || !$student->department_id) { return null; }
        return AcademicStanding::report($student,$student->department_id,$session->name);
    }
}
