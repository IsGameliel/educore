<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\CourseRegistration;
use App\Models\Courses;
use App\Models\Department;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminCourseRegistrationController extends Controller
{
    public function bulkUpdateSessionLevel(Request $request)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'target_session' => 'required|exists:academic_sessions,name',
            'level' => 'required|in:100,200,300,400,500,600',
            'scope' => 'required|in:selected,all_matching',
            'student_ids' => 'required_if:scope,selected|array|min:1',
            'student_ids.*' => 'required|integer|distinct',
            'current_level' => 'nullable|in:100,200,300,400,500,600',
            'q' => 'nullable|string|max:255',
        ]);
        $count = DB::transaction(function () use ($request, $data) {
            $query = User::where('usertype', 'student')->where('department_id', $data['department_id'])
                ->when($data['current_level'] ?? null, fn ($q, $level) => $q->where('level', $level))
                ->when($data['q'] ?? null, function ($query, $search) {
                    $query->where(fn ($q) => $q->where('name', 'like', '%'.trim($search).'%')
                        ->orWhere('email', 'like', '%'.trim($search).'%')->orWhere('matric_number', 'like', '%'.trim($search).'%'));
                });
            if ($data['scope'] === 'selected') { $query->whereIn('id', $data['student_ids']); }
            $students = $query->orderBy('id')->lockForUpdate()->get();
            if ($students->isEmpty() || ($data['scope'] === 'selected' && $students->count() !== count($data['student_ids']))) {
                throw \Illuminate\Validation\ValidationException::withMessages(['student_ids' => 'Select students from the chosen department and filters. No levels were changed.']);
            }
            $conflicts = \App\Models\Result::withoutGlobalScopes()->whereIn('user_id', $students->pluck('id'))
                ->where('session', $data['target_session'])
                ->where(fn ($q) => $q->whereNull('level')->orWhere('level', '!=', $data['level']))->distinct()->pluck('user_id');
            if ($conflicts->isNotEmpty()) {
                $names = $students->whereIn('id', $conflicts)->take(5)->map(fn ($s) => $s->matric_number ?: $s->name)->join(', ');
                throw \Illuminate\Validation\ValidationException::withMessages(['level' => "{$conflicts->count()} student(s) have results with a different level for this session: {$names}. No levels were changed."]);
            }
            $session = AcademicSession::where('name', $data['target_session'])->firstOrFail();
            foreach ($students as $student) {
                \App\Models\StudentAcademicSession::updateOrCreate(
                    ['user_id' => $student->id, 'academic_session_id' => $session->id], ['level' => $data['level']]
                );
            }
            ActivityLogger::log($request->user(), 'student_session_levels_bulk_updated', 'Saved student session levels in bulk.', [
                'department_id' => (int) $data['department_id'], 'properties' => [
                    'session' => $session->name, 'level' => $data['level'], 'student_ids' => $students->pluck('id')->all(),
                ],
            ]);
            return $students->count();
        });
        return redirect()->route('admin.course-registrations.index', [
            'department_id' => $data['department_id'], 'session' => $data['target_session'],
            'current_level' => $data['current_level'] ?? null, 'q' => $data['q'] ?? null,
        ])->with('success', "Session level {$data['level']} saved for {$count} student(s) in {$data['target_session']}. Current profile levels were left unchanged.");
    }

    public function updateSessionLevel(User $student, Request $request)
    {
        abort_unless($student->dashboardRole() === 'student', 404);
        $data = $request->validate([
            'session' => 'required|exists:academic_sessions,name',
            'level' => 'required|in:100,200,300,400,500,600',
            'semester' => 'required|in:First,Second',
        ]);
        DB::transaction(function () use ($student, $request, $data) {
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $session = AcademicSession::where('name', $data['session'])->firstOrFail();
            if (\App\Models\Result::withoutGlobalScopes()->where('user_id', $student->id)
                ->where('session', $session->name)->where(fn ($q) => $q->whereNull('level')->orWhere('level', '!=', $data['level']))->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'level' => 'Existing results have a different level for this session. Correct those academic records before changing the session level.',
                ]);
            }
            $record = \App\Models\StudentAcademicSession::updateOrCreate(
                ['user_id' => $student->id, 'academic_session_id' => $session->id], ['level' => $data['level']]
            );
            ActivityLogger::log($request->user(), 'student_session_level_updated', 'Saved student level for academic session.', [
                'target_user' => $student, 'properties' => ['session' => $session->name, 'level' => $record->level],
            ]);
        });
        return redirect()->route('admin.course-registrations.edit', [
            'student' => $student->id, 'session' => $data['session'], 'semester' => $data['semester'],
        ])->with('success', 'Session level saved. Current profile level was left unchanged.');
    }

    private function normalizeSemester($semester)
    {
        $semester = strtolower(trim((string) $semester));

        if (in_array($semester, ['1', 'first', 'first semester'])) return 'First';
        if (in_array($semester, ['2', 'second', 'second semester'])) return 'Second';

        return 'First';
    }

    public function updateCreditLimit(User $student, Request $request)
    {
        abort_unless($student->dashboardRole() === 'student', 404);
        $data = $request->validate([
            'session' => ['required', 'exists:academic_sessions,name'],
            'semester' => ['required', 'in:First,Second'],
            'credit_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);
        DB::transaction(function () use ($student, $request, $data) {
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $key = ['user_id' => $student->id, 'session' => $data['session'], 'semester' => $data['semester']];
            $before = \App\Services\Academic\StudentCreditLimit::for($student, $data['session'], $data['semester']);
            if (($data['credit_limit'] ?? null) === null) {
                DB::table('student_credit_limits')->where($key)->delete();
            } else {
                DB::table('student_credit_limits')->updateOrInsert($key, [
                    'credit_limit' => $data['credit_limit'], 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            ActivityLogger::log($request->user(), 'credit_limit_updated', 'Updated student registration credit limit', [
                'target_user' => $student, 'department_id' => $student->department_id,
                'properties' => $key + ['before' => $before, 'after' => \App\Services\Academic\StudentCreditLimit::for($student, $data['session'], $data['semester'])],
            ]);
        });

        return back()->with('success', 'Student credit limit updated.');
    }

    public function index(Request $request)
    {
        $currentSession = $request->query('session', 'all');
        $academicSessions = $this->getAcademicSessionOptions([$currentSession]);
        $departments = Department::orderBy('name')->get(['id', 'name']);

        $students = User::query()
            ->where('usertype', 'student')
            ->when($request->filled('department_id'), fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('current_level'), fn ($q) => $q->where('level', $request->current_level))
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->q);

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('matric_number', 'like', "%{$search}%");
                });
            })
            ->with('department')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $sessionLevels = \App\Services\Academic\StudentSessionLevel::forStudents($students->getCollection());
        return view('admin.course_registrations.index', compact('students', 'currentSession', 'academicSessions', 'departments', 'sessionLevels'));
    }

    public function show(User $student, Request $request)
    {
        $semester = $this->normalizeSemester($request->query('semester', 'First'));
        $session = $request->query('session', 'all');
        $academicSessions = $this->getAcademicSessionOptions([$session]);

        $registrations = CourseRegistration::with(['course', 'results'])
            ->where('user_id', $student->id)
            ->where('semester', $semester)
            ->historySession($session)
            ->get();

        $historyGroups = CourseRegistration::where('user_id', $student->id)
            ->selectRaw('session, semester, COUNT(*) as total')->groupBy('session', 'semester')->get();

        $totalCredits = $registrations->sum(fn ($registration) => $registration->course?->credit_unit ?? 0);
        $sessionLevel = \App\Services\Academic\StudentSessionLevel::find($student, $session);

        return view('admin.course_registrations.show', compact(
            'student',
            'semester',
            'session',
            'academicSessions',
            'registrations',
            'totalCredits',
            'historyGroups',
            'sessionLevel'
        ));
    }

    public function edit(User $student, Request $request)
    {
        $semester = $this->normalizeSemester($request->query('semester', 'First'));
        $session = $request->query('session', $this->getCurrentAcademicSession());
        $academicSessions = $this->getAcademicSessionOptions([$session]);
        $sessionLevel = \App\Services\Academic\StudentSessionLevel::find($student, $session);

        $registeredCourseIds = CourseRegistration::where('user_id', $student->id)
            ->where('semester', $semester)
            ->where('session', $session)
            ->pluck('course_id')
            ->toArray();

        $registeredStatuses = CourseRegistration::where('user_id', $student->id)
            ->where('semester', $semester)
            ->where('session', $session)
            ->pluck('status', 'course_id')
            ->toArray();

        $courses = Courses::where('department_id', $student->department_id)
            ->forAcademicSession($session)
            ->where('level', $sessionLevel ?? '')
            ->where('semester', $semester)
            ->with('prerequisites')
            ->orderBy('code')
            ->get();

        return view('admin.course_registrations.edit', compact(
            'student',
            'semester',
            'session',
            'academicSessions',
            'courses',
            'registeredCourseIds',
            'registeredStatuses'
        ) + ['sessionLevel' => $sessionLevel]);
    }

    public function update(User $student, Request $request)
    {
        $actor = Auth::user();
        $semester = $this->normalizeSemester($request->input('semester', 'First'));

        $data = $request->validate([
            'session' => ['required', 'string', 'max:9'],
            'course_ids' => ['array'],
            'course_ids.*' => ['integer'],
            'statuses' => ['array'],
            'statuses.*' => ['in:registered,pending,approved,rejected,withdrawn'],
        ]);

        $session = $data['session'];
        $sessionLevel = \App\Services\Academic\StudentSessionLevel::require($student, $session);
        $courseIds = $data['course_ids'] ?? [];

        $courses = Courses::whereIn('id', $courseIds)
            ->where('department_id', $student->department_id)
            ->forAcademicSession($session)
            ->where('level', $sessionLevel)
            ->where('semester', $semester)
            ->with('prerequisites')
            ->get();

        if ($courses->count() !== count($courseIds)) {
            return back()->withErrors([
                'course_ids' => 'One or more selected courses are invalid for this student, semester, or academic session.',
            ]);
        }

        foreach ($courses as $course) {
            foreach ($course->prerequisites as $prereq) {
                $hasPrereq = CourseRegistration::where('user_id', $student->id)
                    ->where('course_id', $prereq->id)
                    ->exists();

                if (! $hasPrereq) {
                    return back()->withErrors([
                        'course_ids' => "Missing prerequisite for {$course->title}: {$prereq->title}",
                    ]);
                }
            }
        }

        $limit = \App\Services\Academic\StudentCreditLimit::for($student, $session, $semester);
        $newTotal = $courses->sum('credit_unit');

        if ($newTotal > $limit) {
            return back()->withErrors([
                'course_ids' => "Credit unit limit exceeded. Max allowed is {$limit}. Selected is {$newTotal}.",
            ]);
        }

        DB::transaction(function () use ($student, $semester, $session, $courseIds, $data, $actor) {
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $activeIds = array_values(array_filter($courseIds, fn($id) => !in_array($data['statuses'][$id] ?? 'registered',['withdrawn','rejected'],true)));
            $conflicts = \App\Services\Academic\TimetableConflicts::forCourses($activeIds);
            if ($conflicts) { throw \Illuminate\Validation\ValidationException::withMessages(['course_ids'=>implode(' ', $conflicts)]); }
            if (! empty($courseIds)) {
                app(\App\Services\TuitionBilling::class)->assertCleared($student, $session, $semester);
            }
            $existingRegistrations = CourseRegistration::with(['course', 'results'])
                ->where('user_id', $student->id)
                ->where('semester', $semester)
                ->where('session', $session)
                ->get()
                ->keyBy('course_id');

            $existing = $existingRegistrations->keys()->all();
            $toDelete = array_diff($existing, $courseIds);
            $toAdd = array_diff($courseIds, $existing);

            if (! empty($toDelete)) {
                foreach ($toDelete as $courseId) {
                    $registration = $existingRegistrations->get($courseId);

                    if (! $registration) {
                        continue;
                    }

                    $registration->acted_by = $actor->id;
                    $registration->save();

                    ActivityLogger::log(
                        $actor,
                        'registration_removed',
                        "Removed {$student->name} from {$registration->course?->code} - {$registration->course?->title} ({$semester} Semester, {$session})",
                        [
                            'subject' => $registration,
                            'target_user' => $student,
                            'department_id' => $registration->course?->department_id ?? $student->department_id,
                            'properties' => [
                                'course_id' => $registration->course_id,
                                'course_code' => $registration->course?->code,
                                'course_title' => $registration->course?->title,
                                'semester' => $semester,
                                'session' => $session,
                                'status' => 'removed',
                            ],
                        ]
                    );
                }

                foreach ($toDelete as $courseId) {
                    $existingRegistrations->get($courseId)->delete();
                }
            }

            foreach ($toAdd as $courseId) {
                $course = Courses::find($courseId);
                $registration = CourseRegistration::create([
                    'user_id' => $student->id,
                    'acted_by' => $actor->id,
                    'course_id' => $courseId,
                    'semester' => $semester,
                    'session' => $session,
                    'registration_date' => now(),
                    'status' => 'registered',
                ]);

                ActivityLogger::log(
                    $actor,
                    'registration_created',
                    "Registered {$student->name} for {$course?->code} - {$course?->title} ({$semester} Semester, {$session})",
                    [
                        'subject' => $registration,
                        'target_user' => $student,
                        'department_id' => $course?->department_id ?? $student->department_id,
                        'properties' => [
                            'course_id' => $courseId,
                            'course_code' => $course?->code,
                            'course_title' => $course?->title,
                            'semester' => $semester,
                            'session' => $session,
                            'status' => 'registered',
                        ],
                    ]
                );
            }

            $statuses = $data['statuses'] ?? [];

            foreach ($courseIds as $courseId) {
                $status = $statuses[$courseId] ?? 'registered';
                $registration = CourseRegistration::where('user_id', $student->id)
                    ->where('semester', $semester)
                    ->where('session', $session)
                    ->where('course_id', $courseId)
                    ->first();

                if (! $registration) {
                    continue;
                }

                $previousStatus = $registration->status;
                $registration->update([
                    'status' => $status,
                    'acted_by' => $actor->id,
                ]);

                if ($previousStatus === $status) {
                    continue;
                }

                $course = $registration->course;
                $action = match ($status) {
                    'approved' => 'registration_approved',
                    'rejected' => 'registration_rejected',
                    'withdrawn' => 'registration_withdrawn',
                    default => 'registration_updated',
                };

                ActivityLogger::log(
                    $actor,
                    $action,
                    "Updated {$student->name}'s registration for {$course?->code} - {$course?->title} to {$status}",
                    [
                        'subject' => $registration,
                        'target_user' => $student,
                        'department_id' => $course?->department_id ?? $student->department_id,
                        'properties' => [
                            'course_id' => $courseId,
                            'course_code' => $course?->code,
                            'course_title' => $course?->title,
                            'semester' => $semester,
                            'session' => $session,
                            'old_status' => $previousStatus,
                            'status' => $status,
                        ],
                    ]
                );
            }
        });

        return redirect()
            ->route('admin.course-registrations.show', ['student' => $student->id, 'semester' => $semester, 'session' => $session])
            ->with('success', "Registration updated for {$student->name} ({$semester} Semester, {$session}).");
    }

    private function getCurrentAcademicSession(): string
    {
        $activeAcademicSession = AcademicSession::currentName();

        if ($activeAcademicSession) {
            return $activeAcademicSession;
        }

        $year = (int) now()->format('Y');
        $month = (int) now()->format('n');
        $startYear = $month >= 8 ? $year : $year - 1;

        return $startYear . '/' . ($startYear + 1);
    }

    private function getAcademicSessionOptions(array $extraSessions = []): array
    {
        return AcademicSession::query()
            ->orderByDesc('start_year')
            ->pluck('name')
            ->merge(collect($extraSessions)->filter())
            ->unique()
            ->values()
            ->all();
    }
}
