<?php

namespace App\Http\Controllers;

use App\Mail\AttendanceScanCodeMail;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $classScheduleId = $request->get('class_schedule_id');
        $date = $request->get('date');
        $from = $request->get('from');
        $to = $request->get('to');

        $sessions = AttendanceSession::query()
            ->with(['classSchedule.department', 'classSchedule.course', 'classSchedule.lecturer', 'takenBy'])
            ->withCount([
                'records',
                'records as present_count' => fn ($query) => $query->where('status', 'present'),
                'records as late_count' => fn ($query) => $query->where('status', 'late'),
                'records as absent_count' => fn ($query) => $query->where('status', 'absent'),
                'records as excused_count' => fn ($query) => $query->where('status', 'excused'),
            ])
            ->when(auth()->user()->usertype === 'lecturer', fn ($query) => $query->whereHas(
                'classSchedule',
                fn ($scheduleQuery) => $scheduleQuery->where('lecturer_id', auth()->id())
            ))
            ->when($classScheduleId, fn ($query) => $query->where('class_schedule_id', $classScheduleId))
            ->when($date, fn ($query) => $query->whereDate('attendance_date', $date))
            ->when($from, fn ($query) => $query->whereDate('attendance_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('attendance_date', '<=', $to))
            ->latest('attendance_date')
            ->paginate(15);

        return view('admin.attendance.index', [
            'sessions' => $sessions,
            'classSchedules' => $this->classSchedules(),
            'attendanceRoutePrefix' => $this->routePrefix(),
        ]);
    }

    public function create(Request $request)
    {
        $selectedSchedule = $request->get('class_schedule_id')
            ? $this->classSchedules()->firstWhere('id', (int) $request->get('class_schedule_id'))
            : null;

        return view('admin.attendance.create', [
            'session' => new AttendanceSession([
                'class_schedule_id' => $request->get('class_schedule_id'),
                'attendance_date' => $request->get('date', now()->toDateString()),
            ]),
            'records' => collect(),
            'students' => $selectedSchedule ? $this->studentsForSchedule($selectedSchedule) : collect(),
            'classSchedules' => $this->classSchedules(),
            'selectedSchedule' => $selectedSchedule,
            'statuses' => AttendanceRecord::STATUSES,
            'attendanceRoutePrefix' => $this->routePrefix(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateAttendance($request);
        $schedule = $this->findManagedSchedule($validated['class_schedule_id']);
        $students = $this->studentsForSchedule($schedule);

        if ($students->isEmpty()) {
            return back()->withInput()->withErrors([
                'class_schedule_id' => 'No students were found for this class schedule department and level.',
            ]);
        }

        $session = DB::transaction(function () use ($validated, $students) {
            $session = AttendanceSession::updateOrCreate(
                [
                    'class_schedule_id' => $validated['class_schedule_id'],
                    'attendance_date' => $validated['attendance_date'],
                ],
                [
                    'taken_by' => auth()->id(),
                    'notes' => $validated['notes'] ?? null,
                ]
            );

            $this->syncRecords($session, $students, $validated['attendance'] ?? []);

            return $session;
        });

        return redirect()
            ->route($this->routePrefix() . '.show', $session)
            ->with('success', 'Attendance saved successfully.');
    }

    public function createScanSession(Request $request)
    {
        $validated = $this->validateScanSession($request);
        $schedule = $this->findManagedSchedule($validated['class_schedule_id']);
        $students = $this->studentsForSchedule($schedule);

        if ($students->isEmpty()) {
            return back()->withInput()->withErrors([
                'class_schedule_id' => 'No students were found for this class schedule department and level.',
            ]);
        }

        $session = AttendanceSession::create([
            'class_schedule_id' => $validated['class_schedule_id'],
            'attendance_date' => $validated['attendance_date'],
            'taken_by' => auth()->id(),
            'scan_token' => Str::random(48),
            'scan_expires_at' => now()->addMinutes(2),
            'scan_code_sent_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ])->load(['classSchedule.course', 'classSchedule.department']);

        $this->emailScanCode($session, $students);

        return redirect()
            ->route($this->routePrefix() . '.show', $session)
            ->with('success', 'Attendance scan code generated and emailed to eligible students.');
    }

    public function show(AttendanceSession $attendance)
    {
        $this->authorizeScanCodeManager($attendance);

        $attendance->load([
            'classSchedule.department',
            'classSchedule.course',
            'classSchedule.lecturer',
            'takenBy',
            'records.student.department',
        ]);

        return view('admin.attendance.show', [
            'session' => $attendance,
            'summary' => $attendance->records->countBy('status'),
            'attendanceRoutePrefix' => $this->routePrefix(),
            'scanUrl' => $attendance->scan_token ? route('attendance.scan.register', $attendance->scan_token) : null,
        ]);
    }

    public function edit(AttendanceSession $attendance)
    {
        $this->authorizeScanCodeManager($attendance);
        $attendance->load(['classSchedule.department', 'classSchedule.course', 'classSchedule.lecturer', 'records']);

        return view('admin.attendance.edit', [
            'session' => $attendance,
            'records' => $attendance->records->keyBy('student_id'),
            'students' => $this->studentsForSchedule($attendance->classSchedule),
            'classSchedules' => $this->classSchedules(),
            'selectedSchedule' => $attendance->classSchedule,
            'statuses' => AttendanceRecord::STATUSES,
            'attendanceRoutePrefix' => $this->routePrefix(),
        ]);
    }

    public function update(Request $request, AttendanceSession $attendance)
    {
        $this->authorizeScanCodeManager($attendance);
        $validated = $this->validateAttendance($request, $attendance->id);
        $schedule = $this->findManagedSchedule($validated['class_schedule_id']);
        $students = $this->studentsForSchedule($schedule);

        DB::transaction(function () use ($attendance, $validated, $students) {
            $attendance->update([
                'class_schedule_id' => $validated['class_schedule_id'],
                'attendance_date' => $validated['attendance_date'],
                'taken_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->syncRecords($attendance, $students, $validated['attendance'] ?? []);
        });

        return redirect()
            ->route($this->routePrefix() . '.show', $attendance)
            ->with('success', 'Attendance updated successfully.');
    }

    public function sendScanCode(AttendanceSession $attendance)
    {
        $this->authorizeScanCodeManager($attendance);
        $attendance->load(['classSchedule.course', 'classSchedule.department', 'classSchedule.lecturer']);
        $students = $this->studentsForSchedule($attendance->classSchedule);

        if ($students->isEmpty()) {
            return back()->withErrors([
                'class_schedule_id' => 'No students were found for this class schedule department and level.',
            ]);
        }

        $attendance->forceFill([
            'scan_token' => Str::random(48),
            'scan_expires_at' => now()->addMinutes(2),
            'scan_code_sent_at' => now(),
        ])->save();

        $this->emailScanCode($attendance->fresh(['classSchedule.course', 'classSchedule.department']), $students);

        return back()->with('success', 'Attendance scan code emailed to eligible students.');
    }

    public function registerByScan(string $token)
    {
        $session = AttendanceSession::query()
            ->with(['classSchedule.course', 'classSchedule.department'])
            ->where('scan_token', $token)
            ->firstOrFail();

        abort_if($session->scan_expires_at && $session->scan_expires_at->isPast(), 403, 'This attendance scan code has expired.');

        $student = auth()->user();

        abort_if($student->usertype !== 'student', 403, 'Only students can register attendance.');
        abort_if((string) $student->department_id !== (string) $session->classSchedule->department_id, 403, 'This attendance code is not for your department.');
        abort_if((string) $student->level !== (string) $session->classSchedule->level, 403, 'This attendance code is not for your level.');

        $record = AttendanceRecord::updateOrCreate(
            [
                'attendance_session_id' => $session->id,
                'student_id' => $student->id,
            ],
            [
                'status' => 'present',
                'reason' => 'Registered by scan code',
                'registered_at' => now(),
            ]
        );

        return view('student.attendance-success', compact('session', 'record'));
    }

    public function destroy(AttendanceSession $attendance)
    {
        $this->authorizeScanCodeManager($attendance);
        $attendance->delete();

        return redirect()
            ->route($this->routePrefix() . '.index')
            ->with('success', 'Attendance session deleted successfully.');
    }

    private function validateAttendance(Request $request, ?int $sessionId = null): array
    {
        return $request->validate([
            'class_schedule_id' => [
                'required',
                'exists:class_schedules,id',
                function ($attribute, $value, $fail) use ($request, $sessionId) {
                    if (!$request->filled('attendance_date') || strtotime($request->input('attendance_date')) === false) {
                        return;
                    }

                    $exists = AttendanceSession::query()
                        ->where('class_schedule_id', $value)
                        ->whereDate('attendance_date', $request->input('attendance_date'))
                        ->when($sessionId, fn ($query) => $query->whereKeyNot($sessionId))
                        ->exists();

                    if ($exists) {
                        $fail('Attendance has already been taken for this class on the selected date.');
                    }
                },
            ],
            'attendance_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'attendance' => ['array'],
            'attendance.*.status' => ['required', 'in:' . implode(',', AttendanceRecord::STATUSES)],
            'attendance.*.reason' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function validateScanSession(Request $request): array
    {
        return $request->validate([
            'class_schedule_id' => [
                'required',
                'exists:class_schedules,id',
                function ($attribute, $value, $fail) use ($request) {
                    if (!$request->filled('attendance_date') || strtotime($request->input('attendance_date')) === false) {
                        return;
                    }

                    $exists = AttendanceSession::query()
                        ->where('class_schedule_id', $value)
                        ->whereDate('attendance_date', $request->input('attendance_date'))
                        ->exists();

                    if ($exists) {
                        $fail('Attendance has already been created for this class on the selected date. Open that session to resend its scan code.');
                    }
                },
            ],
            'attendance_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function syncRecords(AttendanceSession $session, $students, array $attendance): void
    {
        $validStudentIds = $students->pluck('id')->all();

        foreach ($students as $student) {
            $record = $attendance[$student->id] ?? [];
            $status = in_array($record['status'] ?? null, AttendanceRecord::STATUSES, true)
                ? $record['status']
                : 'present';

            AttendanceRecord::updateOrCreate(
                [
                    'attendance_session_id' => $session->id,
                    'student_id' => $student->id,
                ],
                [
                    'status' => $status,
                    'reason' => $record['reason'] ?? null,
                    'registered_at' => now(),
                ]
            );
        }

        $session->records()
            ->whereNotIn('student_id', $validStudentIds)
            ->delete();
    }

    private function studentsForSchedule(ClassSchedule $schedule)
    {
        return User::query()
            ->with('department')
            ->where('usertype', 'student')
            ->where('department_id', $schedule->department_id)
            ->where('level', $schedule->level)
            ->orderBy('name')
            ->get();
    }

    private function classSchedules()
    {
        return ClassSchedule::query()
            ->with(['department', 'course', 'lecturer'])
            ->when(auth()->user()->usertype === 'lecturer', fn ($query) => $query->where('lecturer_id', auth()->id()))
            ->orderBy('department_id')
            ->orderBy('level')
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();
    }

    private function findManagedSchedule(int|string $scheduleId): ClassSchedule
    {
        return ClassSchedule::query()
            ->when(auth()->user()->usertype === 'lecturer', fn ($query) => $query->where('lecturer_id', auth()->id()))
            ->findOrFail($scheduleId);
    }

    private function emailScanCode(AttendanceSession $session, $students): void
    {
        foreach ($students as $student) {
            Mail::to($student->email)->send(new AttendanceScanCodeMail($session, $student));
        }
    }

    private function authorizeScanCodeManager(AttendanceSession $session): void
    {
        $session->loadMissing('classSchedule');
        $user = auth()->user();

        if ($user->usertype === 'admin') {
            return;
        }

        if ($user->usertype === 'lecturer' && (int) $session->classSchedule->lecturer_id === (int) $user->id) {
            return;
        }

        abort(403, 'You cannot manage this attendance.');
    }

    private function routePrefix(): string
    {
        return str_starts_with(request()->route()?->getName() ?? '', 'lecturer.')
            ? 'lecturer.attendance'
            : 'admin.attendance';
    }
}
