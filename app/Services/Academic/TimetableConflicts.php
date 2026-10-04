<?php

namespace App\Services\Academic;

use App\Models\{AcademicSession, ClassSchedule, Courses, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimetableConflicts
{
    public static function overlaps($a, $b): bool
    {
        return strtolower(trim($a->day)) === strtolower(trim($b->day))
            && substr($a->start_time, 0, 5) < substr($b->end_time, 0, 5)
            && substr($b->start_time, 0, 5) < substr($a->end_time, 0, 5);
    }

    public function save(array $data, ?ClassSchedule $existing = null): ClassSchedule
    {
        return DB::transaction(function () use ($data, $existing) {
            $course = Courses::findOrFail($data['subject']);
            AcademicSession::whereIn('id', array_filter([$course->academic_session_id, $existing?->course?->academic_session_id]))->orderBy('id')->lockForUpdate()->get();
            if (!$course->academic_session_id || (string)$course->department_id !== (string)$data['department_id'] || (string)$course->level !== (string)$data['level'] || $course->semester !== $data['semester']) {
                throw ValidationException::withMessages(['subject'=>'Choose a course belonging to the selected department, level and semester, with an academic session.']);
            }
            if (!User::whereKey($data['lecturer_id'])->where('usertype','lecturer')->exists()) {
                throw ValidationException::withMessages(['lecturer_id'=>'Choose a lecturer.']);
            }
            $candidate = new ClassSchedule($data);
            $others = ClassSchedule::with('course')->where('semester',$data['semester'])
                ->whereHas('course', fn($q) => $q->where('academic_session_id',$course->academic_session_id))
                ->when($existing, fn($q) => $q->whereKeyNot($existing->id))->get();
            foreach ($others as $other) {
                if (!self::overlaps($candidate, $other)) { continue; }
                $reasons = [];
                if ((string)$other->lecturer_id === (string)$candidate->lecturer_id) { $reasons[] = 'lecturer'; }
                if (mb_strtolower(trim($other->room)) === mb_strtolower(trim($candidate->room))) { $reasons[] = 'room'; }
                if ((string)$other->department_id === (string)$candidate->department_id && (string)$other->level === (string)$candidate->level) { $reasons[] = 'department/level'; }
                if ($reasons) {
                    throw ValidationException::withMessages(['start_time'=>'Timetable conflict: '.implode(', ', $reasons).' already booked for '.$other->course->code.' on '.$other->day.' '.substr($other->start_time,0,5).'–'.substr($other->end_time,0,5).'.']);
                }
            }
            if ($existing) { $existing->update($data); return $existing; }
            return ClassSchedule::create($data);
        }, 3);
    }

    public static function forCourses(array $ids): array
    {
        $slots = ClassSchedule::with('course')->whereIn('subject',$ids)->get();
        $conflicts = [];
        foreach ($slots as $i => $a) {
            foreach ($slots->slice($i + 1) as $b) {
                if ($a->subject !== $b->subject && $a->semester === $b->semester && self::overlaps($a,$b)) {
                    $conflicts[] = $a->course?->code.' and '.$b->course?->code.' overlap on '.$a->day.'.';
                }
            }
        }
        return array_values(array_unique($conflicts));
    }
}
