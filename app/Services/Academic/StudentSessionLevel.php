<?php

namespace App\Services\Academic;

use App\Models\AcademicSession;
use App\Models\StudentAcademicSession;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class StudentSessionLevel
{
    public static function forStudents(\Illuminate\Support\Collection $students): array
    {
        $records = \Illuminate\Support\Facades\DB::table('student_academic_sessions as history')
            ->join('academic_sessions as session', 'session.id', '=', 'history.academic_session_id')
            ->whereIn('history.user_id', $students->pluck('id'))->get(['history.user_id', 'history.level', 'session.name'])->groupBy('user_id');
        $current = AcademicSession::current();
        return $students->mapWithKeys(function ($student) use ($records, $current) {
            $levels = $current ? [$current->name => (string) $student->level] : [];
            foreach ($records->get($student->id, collect()) as $record) { $levels[$record->name] = $record->level; }
            return [$student->id => $levels];
        })->all();
    }

    public static function find(User $student, string $session): ?string
    {
        $academicSession = AcademicSession::where('name', $session)->first();
        if (! $academicSession) { return null; }
        $record = StudentAcademicSession::where('user_id', $student->id)
            ->where('academic_session_id', $academicSession->id)->first();
        if ($record) { return $record->level; }
        // Only the active session can safely use the current profile level.
        return $academicSession->id === AcademicSession::current()?->id ? (string) $student->level : null;
    }

    public static function require(User $student, string $session): string
    {
        return self::find($student, $session) ?? throw ValidationException::withMessages([
            'session_level' => "Set the student's level for {$session} in Course Registrations before uploading results.",
        ]);
    }

    public static function snapshot(User $student, AcademicSession $session, string $level): void
    {
        StudentAcademicSession::firstOrCreate(['user_id' => $student->id, 'academic_session_id' => $session->id], ['level' => $level]);
    }
}
