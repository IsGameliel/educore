<?php

namespace App\Services\Academic;

use App\Models\Result;
use App\Models\User;

class ResultAccess
{
    public static function manager(User $user): bool
    {
        return in_array($user->dashboardRole(), ['admin', 'exam_officer'], true);
    }

    public static function stage(User $user, int $departmentId, string $stage): bool
    {
        return self::manager($user);
    }

    public static function department(User $user, int $departmentId): bool
    {
        return self::manager($user);
    }

    public static function scope($query, User $user)
    {
        if (self::manager($user)) {
            return $query;
        }
        if ($user->dashboardRole() === 'student') {
            return $query->where('user_id', $user->id)->where('workflow_status', 'published');
        }
        $departments = collect();
        $courses = $user->dashboardRole() === 'lecturer'
            ? $user->assignedCourses()->with('academicSession')->get() : collect();

        return $query->where(function ($query) use ($departments, $courses) {
            $query->whereIn('department_id', $departments);
            foreach ($courses as $course) {
                if (! $course->academicSession) {
                    continue;
                }
                $query->orWhere(fn ($q) => $q->where('course_code', $course->code)
                    ->where('department_id', $course->department_id)
                    ->where('semester', $course->semester)->where('session', $course->academicSession->name));
            }
        });
    }

    public static function view(User $user, Result $result): void
    {
        abort_unless(self::scope(Result::query(), $user)->whereKey($result->id)->exists(), 403);
    }

    public static function edit(User $user, Result $result): void
    {
        abort_if($user->dashboardRole() === 'student', 403);
        self::view($user, $result);
    }
}
