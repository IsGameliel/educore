<?php

namespace App\Services;

use App\Models\{AcademicSession, User};

class EnrollmentData
{
    public static function issues(User $student, ?AcademicSession $session = null): array
    {
        $issues = [];
        if (! $student->department_id || ! $student->department()->exists()) { $issues[] = 'Missing department'; }
        if (! in_array((string) $student->level, ['100','200','300','400','500','600'], true)) { $issues[] = 'Missing or invalid level'; }
        if (! $student->entry_year || $student->entry_year < 1900 || $student->entry_year > 2100) {
            $issues[] = 'Missing or invalid entry year';
        } elseif ($session && $student->entry_year > $session->start_year) {
            $issues[] = 'Entry year is after the active session; review session or enrollment';
        }
        return $issues;
    }

    public static function incompleteQuery(?AcademicSession $session = null)
    {
        return User::where('usertype', 'student')->where(function ($query) use ($session) {
            $query->whereDoesntHave('department')->orWhereNull('level')->orWhereNotIn('level', ['100','200','300','400','500','600'])
                ->orWhereNull('entry_year')->orWhere('entry_year', '<', 1900)->orWhere('entry_year', '>', 2100);
            if ($session) { $query->orWhere('entry_year', '>', $session->start_year); }
        });
    }
}
