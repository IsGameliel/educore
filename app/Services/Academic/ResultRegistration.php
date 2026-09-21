<?php

namespace App\Services\Academic;

use App\Models\CourseRegistration;
use App\Models\Courses;
use App\Models\Result;
use Illuminate\Validation\ValidationException;

class ResultRegistration
{
    public const ELIGIBLE_STATUSES = ['registered', 'approved', 'completed'];

    public static function requireForCourse(int $studentId, Courses $course, string $session, string $semester): CourseRegistration
    {
        $registrations = CourseRegistration::where('user_id', $studentId)->where('course_id', $course->id)
            ->where('session', $session)->where('semester', $semester)
            ->whereIn('status', self::ELIGIBLE_STATUSES)->get();
        if ($course->academicSession?->name !== $session || $course->semester !== $semester || $registrations->count() !== 1) {
            throw ValidationException::withMessages(['course_registration' => 'The student must have one active registration for this course, session and semester before a result can be entered.']);
        }

        return $registrations->first();
    }

    public static function matching(Result $result)
    {
        return CourseRegistration::where('user_id', $result->user_id)
            ->where('session', $result->session)->where('semester', $result->semester)
            ->whereHas('course', fn ($q) => $q->where('code', $result->course_code)
                ->where('department_id', $result->department_id)->where('semester', $result->semester)
                ->forAcademicSession($result->session));
    }

    public static function attach(Result $result): void
    {
        if (! $result->course_registration_id) {
            $ids = self::matching($result)->whereIn('status', self::ELIGIBLE_STATUSES)->pluck('id');
            if ($ids->count() !== 1) {
                throw ValidationException::withMessages(['course_registration' => 'Create or correct the student registration for this course/session before saving the result.']);
            }
            $result->course_registration_id = $ids->first();
        }
        self::validate($result);
    }

    public static function validate(Result $result): void
    {
        if (! self::matching($result)->whereKey($result->course_registration_id)
            ->whereIn('status', self::ELIGIBLE_STATUSES)->exists()) {
            throw ValidationException::withMessages(['course_registration' => 'This result does not match an active course registration. Check the student, course, session and semester.']);
        }
    }
}
