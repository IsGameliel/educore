<?php

namespace App\Http\Controllers;

use App\Models\{AttendanceRecord, Result};
use App\Services\Academic\{RegistrationAssistance, ResultAccess};
use Illuminate\Http\Request;

class AcademicAssistanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user->dashboardRole();
        abort_unless(in_array($role,['admin','exam_officer','lecturer','student'],true),403);
        $filters = $request->validate(['from'=>'nullable|date_format:Y-m-d','to'=>'nullable|date_format:Y-m-d|after_or_equal:from']);
        $from = $filters['from'] ?? today()->subDays(30)->toDateString();
        $to = $filters['to'] ?? today()->toDateString();
        $attendance = AttendanceRecord::query()->join('attendance_sessions','attendance_sessions.id','=','attendance_records.attendance_session_id')
            ->join('class_schedules','class_schedules.id','=','attendance_sessions.class_schedule_id')
            ->join('courses','courses.id','=','class_schedules.subject')
            ->join('users','users.id','=','attendance_records.student_id')
            ->whereBetween('attendance_sessions.attendance_date',[$from,$to])->whereDate('attendance_sessions.attendance_date','<=',today())
            ->where(fn($q)=>$q->whereNull('attendance_sessions.scan_expires_at')->orWhere('attendance_sessions.scan_expires_at','<=',now()))
            ->when($role === 'student',fn($q)=>$q->where('attendance_records.student_id',$user->id))
            ->when($role === 'lecturer',fn($q)=>$q->where('class_schedules.lecturer_id',$user->id))
            ->selectRaw("users.id as student_id, users.name as student_name, courses.id as course_id, courses.code, COUNT(*) as total, SUM(CASE WHEN attendance_records.status = 'present' THEN 1 ELSE 0 END) as present, SUM(CASE WHEN attendance_records.status = 'late' THEN 1 ELSE 0 END) as late, SUM(CASE WHEN attendance_records.status = 'absent' THEN 1 ELSE 0 END) as absent, SUM(CASE WHEN attendance_records.status = 'excused' THEN 1 ELSE 0 END) as excused")
            ->groupBy('users.id','users.name','courses.id','courses.code')->orderBy('users.name')->orderBy('courses.code')->paginate(30)->withQueryString();
        $reviews = collect();
        if ($role !== 'student') {
            $reviews = ResultAccess::scope(Result::query(),$user)->whereIn('workflow_status', $role === 'lecturer' ? ['draft'] : ['submitted','reviewed','approved'])
                ->where('updated_at','<=',now()->subDays(3))->selectRaw('department_id, session, semester, course_code, workflow_status, COUNT(*) as total, MIN(updated_at) as waiting_since, MIN(id) as example_id')
                ->groupBy('department_id','session','semester','course_code','workflow_status')->orderBy('waiting_since')->limit(100)->get();
        }
        $assistance = $role === 'student' ? RegistrationAssistance::forStudent($user) : [];
        return view('academic.assistance',compact('attendance','reviews','assistance','from','to','role'));
    }
}
