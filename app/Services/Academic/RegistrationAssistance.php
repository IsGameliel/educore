<?php

namespace App\Services\Academic;

use App\Models\{AcademicSession, CourseRegistration, Courses, User};

class RegistrationAssistance
{
    public static function forStudent(User $student): array
    {
        $session = AcademicSession::current();
        if (!$session) { return []; }
        $history = CourseRegistration::where('user_id',$student->id)->pluck('course_id');
        $carryovers = CarryoverRegistration::available($student, $session->name);
        $out = [];
        foreach (['First','Second'] as $semester) {
            $registered = CourseRegistration::where('user_id',$student->id)->where('session',$session->name)->where('semester',$semester)->pluck('course_id');
            $courses = Courses::with('prerequisites')->forAcademicSession($session->name)->where('department_id',$student->department_id)
                ->where('semester',$semester)->where('level',$student->level)->get()->merge($carryovers->where('semester',$semester))->unique('id');
            $remaining = $courses->whereNotIn('id',$registered);
            $out[$semester] = [
                'session'=>$session->name, 'registered'=>$registered->count(),
                'units'=>CourseRegistration::getTotalCreditUnitsForSemester($student->id,$semester,$session->name),
                'limit'=>StudentCreditLimit::for($student,$session->name,$semester),
                'clearance'=>RegistrationAccess::status($student,$session->name,$semester),
                'courses'=>$remaining->map(fn($course) => ['code'=>$course->code,'units'=>$course->credit_unit,
                    'carryover'=>$carryovers->contains('id',$course->id),
                    'missing'=>$course->prerequisites->whereNotIn('id',$history)->pluck('code')->all()])->values(),
                'conflicts'=>TimetableConflicts::forCourses($registered->all()),
            ];
        }
        return $out;
    }
}
