<?php

namespace App\Services\Academic;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class StudentCreditLimit
{
    public static function for(User $student, string $session, string $semester): int
    {
        return (int) (DB::table('student_credit_limits')
            ->where('user_id', $student->id)->where('session', $session)->where('semester', $semester)
            ->value('credit_limit') ?? (in_array((string) $student->level, ['100', '200', '300', '400'], true) ? 24 : 30));
    }
}
