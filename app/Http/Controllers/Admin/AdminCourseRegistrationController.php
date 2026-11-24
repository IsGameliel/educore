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
    public function bulkCreate(Request $request)
    {
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'session' => 'nullable|exists:academic_sessions,name',
            'semester' => 'nullable|in:First,Second',
            'level' => 'nullable|in:100,200,300,400,500,600',
            'include_carryover' => 'nullable|boolean',
        ]);
        $sessions = $this->getAcademicSessionOptions([]);
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $session = $request->query('session', AcademicSession::current()?->name);
        $semester = $request->query('semester', 'First');
        $departmentId = $request->query('department_id');
        $level = $request->query('level');
        $includeCarryover = $request->boolean('include_carryover') && (int) $level > 100;
        $students = collect();
        $courses = collect();
        $usingOtherSessionCatalog = false;
        if ($departmentId && $session && $level) {
            $academicSessionId = AcademicSession::where('name', $session)->value('id');
            $students = User::where('usertype', 'student')->where('department_id', $departmentId)
                ->orderBy('name')->get()->filter(fn ($student) => \App\Services\Academic\StudentSessionLevel::find($student, $session) === $level)->values();
            $courseQuery = Courses::where('department_id', $departmentId)
                ->where(function ($query) use ($level, $includeCarryover) {
                    $query->whereRaw('TRIM(level) = ?', [(string) $level]);
                    if ($includeCarryover) {
                        $query->orWhereRaw('TRIM(level) = ?', [(string) ((int) $level - 100)]);
                    }
                })
                ->whereRaw('LOWER(TRIM(semester)) = ?', [strtolower(trim($semester))]);
            $courses = (clone $courseQuery)->where('academic_session_id', $academicSessionId)
                ->with('academicSession')->orderBy('code')->get();
            if ($courses->isEmpty()) {
                // Keep the workflow usable when a school has not copied its catalog
                // forward yet, while exposing the source session and requiring explicit
                // confirmation before those courses can be saved for the chosen session.
                $courses = $courseQuery->with('academicSession')->orderBy('code')->get();
                $usingOtherSessionCatalog = $courses->isNotEmpty();
            }
        }
        return view('admin.course_registrations.bulk', compact('sessions', 'departments', 'session', 'semester', 'departmentId', 'level', 'students', 'courses', 'usingOtherSessionCatalog', 'includeCarryover'));
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id', 'session' => 'required|exists:academic_sessions,name',
            'semester' => 'required|in:First,Second', 'level' => 'required|in:100,200,300,400,500,600',
            'student_ids' => 'required|array|min:1', 'student_ids.*' => 'required|integer|distinct|exists:users,id',
            'course_ids' => 'required|array|min:1', 'course_ids.*' => 'required|integer|distinct|exists:courses,id',
            'include_carryover' => 'nullable|boolean',
        ]);
        $students = User::where('usertype', 'student')->where('department_id', $data['department_id'])->whereIn('id', $data['student_ids'])->get();
        if ($students->count() !== count($data['student_ids']) || $students->contains(fn ($student) => \App\Services\Academic\StudentSessionLevel::find($student, $data['session']) !== $data['level'])) {
            return back()->withInput()->withErrors(['student_ids' => 'Every selected student must belong to the chosen department and have the chosen level for this session.']);
        }
        $academicSessionId = AcademicSession::where('name', $data['session'])->value('id');
        $includeCarryover = $request->boolean('include_carryover') && (int) $data['level'] > 100;
        $courses = Courses::whereIn('id', $data['course_ids'])->where('department_id', $data['department_id'])
            ->where(function ($query) use ($data, $includeCarryover) {
                $query->whereRaw('TRIM(level) = ?', [(string) $data['level']]);
                if ($includeCarryover) {
                    $query->orWhereRaw('TRIM(level) = ?', [(string) ((int) $data['level'] - 100)]);
                }
            })
            ->whereRaw('LOWER(TRIM(semester)) = ?', [strtolower(trim($data['semester']))])
            ->with('prerequisites')->get();
        if ($courses->count() !== count($data['course_ids'])) {
            return back()->withInput()->withErrors(['course_ids' => 'Every selected course must match the department, eligible level, session and semester.']);
        }
        $selectedSessionCourses = $courses->filter(fn ($course) => (int) $course->academic_session_id === (int) $academicSessionId);
        if ($selectedSessionCourses->count() !== $courses->count() && $request->input('confirm_other_session_catalog') !== '1') {
            return back()->withInput()->withErrors(['confirm_other_session_catalog' => 'Some selected courses belong to a different academic session. Review their source session and explicitly confirm before registering them for this session.']);
        }
        $catalogSessionIds = $courses->pluck('academic_session_id')->filter()->unique()->values();
        $count = DB::transaction(function () use ($request, $data, $students, $courses, $academicSessionId, $catalogSessionIds) {
            $created = 0;
            foreach ($students as $student) {
                User::whereKey($student->id)->lockForUpdate()->firstOrFail();
                $existing = CourseRegistration::where('user_id', $student->id)->where('session', $data['session'])
                    ->where('semester', $data['semester'])->whereIn('course_id', $courses->pluck('id'))->get()->keyBy('course_id');
                $newCourses = $courses->reject(fn ($course) => $existing->has($course->id));
                if ($newCourses->isNotEmpty()) {
                    app(\App\Services\TuitionBilling::class)->assertCleared($student, $data['session'], $data['semester']);
                }
                $mandatory = Courses::where('department_id', $data['department_id'])
                    ->where(fn ($query) => $query->where('academic_session_id', $academicSessionId)
                        ->when($catalogSessionIds->isNotEmpty(), fn ($query) => $query->orWhereIn('academic_session_id', $catalogSessionIds))
                        ->orWhereNull('academic_session_id'))
                    ->whereRaw('TRIM(level) = ?', [(string) $data['level']])
                    ->whereRaw('LOWER(TRIM(semester)) = ?', [strtolower(trim($data['semester']))])
                    ->whereIn('course_status', ['Required', 'Compulsory'])->pluck('id');
                $alreadyActive = CourseRegistration::where('user_id', $student->id)->where('session', $data['session'])
                    ->where('semester', $data['semester'])->whereIn('status', ['registered', 'approved', 'completed', 'pending'])->pluck('course_id');
                $missingMandatory = $mandatory->diff($alreadyActive->merge($courses->pluck('id')));
                if ($missingMandatory->isNotEmpty()) {
                    $codes = Courses::whereIn('id', $missingMandatory)->pluck('code')->join(', ');
                    throw \Illuminate\Validation\ValidationException::withMessages(['course_ids' => "{$student->name} must also be registered for required courses: {$codes}."]);
                }
                $currentCredits = CourseRegistration::where('user_id', $student->id)->where('session', $data['session'])
                    ->where('semester', $data['semester'])->whereIn('status', ['registered', 'approved', 'completed', 'pending'])
                    ->with('course')->get()->sum(fn ($registration) => $registration->course?->credit_unit ?? 0);
                $limit = \App\Services\Academic\StudentCreditLimit::for($student, $data['session'], $data['semester']);
                if ($currentCredits + $newCourses->sum('credit_unit') > $limit) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['course_ids' => "{$student->name} would exceed the {$limit} credit unit limit."]);
                }
                $activeIds = CourseRegistration::where('user_id', $student->id)->where('session', $data['session'])->where('semester', $data['semester'])
                    ->whereIn('status', ['registered', 'approved', 'completed', 'pending'])->pluck('course_id')->merge($newCourses->pluck('id'))->unique()->all();
                if ($conflict = \App\Services\Academic\TimetableConflicts::forCourses($activeIds)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['course_ids' => $student->name.': '.implode(' ', $conflict)]);
                }
                foreach ($newCourses as $course) {
                    foreach ($course->prerequisites as $prerequisite) {
                        if (! CourseRegistration::where('user_id', $student->id)->where('course_id', $prerequisite->id)->exists()) {
                            throw \Illuminate\Validation\ValidationException::withMessages(['course_ids' => "{$student->name} is missing prerequisite {$prerequisite->code} for {$course->code}."]);
                        }
                    }
                    $registration = CourseRegistration::create(['user_id' => $student->id, 'acted_by' => $request->user()->id,
                        'course_id' => $course->id, 'semester' => $data['semester'], 'session' => $data['session'],
                        'registration_date' => now(), 'status' => 'registered']);
                    ActivityLogger::log($request->user(), 'registration_created', "Registered {$student->name} for {$course->code} - {$course->title} ({$data['semester']} Semester, {$data['session']})", [
                        'subject' => $registration, 'target_user' => $student, 'department_id' => $course->department_id,
                        'properties' => ['course_id' => $course->id, 'course_code' => $course->code, 'session' => $data['session'], 'semester' => $data['semester'], 'status' => 'registered'],
                    ]);
                    $created++;
                }
                foreach ($existing as $registration) {
                    if (in_array($registration->status, ['withdrawn', 'rejected'], true)) {
                        $registration->update(['status' => 'registered', 'acted_by' => $request->user()->id]);
                        $created++;
                    }
                }
            }
            return $created;
        });
        return redirect()->route('admin.course-registrations.bulk', array_merge(array_intersect_key($data, array_flip(['department_id', 'session', 'semester', 'level'])), ['include_carryover' => $includeCarryover ? 1 : null]))
            ->with('success', "Registered {$count} course enrollment(s) for {$students->count()} student(s). Existing registrations were preserved.");
    }

    public function bulkUpdateSessionLevel(Request $request)
    {
        $data = $request->validate([
            'department_id' => 'required_if:scope,all_matching|nullable|exists:departments,id',
            'target_session' => 'required|exists:academic_sessions,name',
            'level' => 'required|in:100,200,300,400,500,600',
            'scope' => 'required|in:selected,all_matching',
            'student_ids' => 'required_if:scope,selected|array|min:1',
            'student_ids.*' => 'required|integer|distinct',
            'current_level' => 'nullable|in:100,200,300,400,500,600',
            'q' => 'nullable|string|max:255',
        ]);
        $count = DB::transaction(function () use ($request, $data) {
            $query = User::where('usertype', 'student')
                ->when($data['department_id'] ?? null, fn ($q, $department) => $q->where('department_id', $department))
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
                'department_id' => isset($data['department_id']) ? (int) $data['department_id'] : null, 'properties' => [
                    'session' => $session->name, 'level' => $data['level'], 'student_ids' => $students->pluck('id')->all(),
                ],
            ]);
            return $students->count();
        });
        return redirect()->route('admin.course-registrations.index', [
            'department_id' => $data['department_id'] ?? null, 'session' => $data['target_session'],
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

    public function updateDepartmentCreditLimit(Request $request)
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'semester' => ['required', 'in:First,Second'],
            'credit_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);
        $key = ['department_id' => $data['department_id'], 'semester' => $data['semester']];
        if ($data['credit_limit'] === null) {
            DB::table('department_credit_limits')->where($key)->delete();
        } else {
            DB::table('department_credit_limits')->updateOrInsert($key, [
                'credit_limit' => $data['credit_limit'], 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        ActivityLogger::log($request->user(), 'department_credit_limit_updated', 'Updated department semester credit limit', [
            'department_id' => $data['department_id'], 'properties' => $key + ['credit_limit' => $data['credit_limit']],
        ]);
        return back()->with('success', 'Department semester credit limit updated.');
    }

    public function index(Request $request)
    {
        $currentSession = $request->query('session', 'all');
        $academicSessions = $this->getAcademicSessionOptions([$currentSession]);
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $departmentCreditLimits = DB::table('department_credit_limits')->get()->keyBy(fn ($row) => $row->department_id.'-'.$row->semester);

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
        return view('admin.course_registrations.index', compact('students', 'currentSession', 'academicSessions', 'departments', 'sessionLevels', 'departmentCreditLimits'));
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
        $activeSessionName = AcademicSession::currentName() ?? $session;
        $activeSessionLevel = \App\Services\Academic\StudentSessionLevel::find($student, $activeSessionName);

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
        $carryoverCourses = $sessionLevel
            ? \App\Services\Academic\CarryoverRegistration::available($student, $session)
                ->where('semester', $semester)->values()
            : collect();
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $previousLevel = $request->query('previous_level');
        $previousDepartmentId = $request->query('previous_department_id', $student->department_id);
        $previousSession = $request->query('previous_session', $activeSessionName);
        $previousLevelCourses = collect();
        $previousRegisteredCourseIds = [];
        if ($previousLevel && $previousDepartmentId && $previousSession) {
            $previousLevelCourses = Courses::where('department_id', $previousDepartmentId)
                ->forAcademicSession($previousSession)->whereRaw('TRIM(level) = ?', [(string) $previousLevel])
                ->where('semester', $semester)->orderBy('code')->get();
            $previousRegisteredCourseIds = CourseRegistration::where('user_id', $student->id)->where('session', $activeSessionName)
                ->where('semester', $semester)->pluck('course_id')->all();
        }

        return view('admin.course_registrations.edit', compact(
            'student',
            'semester',
            'session',
            'academicSessions',
            'courses',
            'registeredCourseIds',
            'registeredStatuses'
        ) + ['sessionLevel' => $sessionLevel, 'activeSessionName' => $activeSessionName, 'activeSessionLevel' => $activeSessionLevel,
            'carryoverCourses' => $carryoverCourses, 'previousLevelCourses' => $previousLevelCourses,
            'departments' => $departments, 'previousLevel' => $previousLevel, 'previousDepartmentId' => $previousDepartmentId,
            'previousSession' => $previousSession, 'previousRegisteredCourseIds' => $previousRegisteredCourseIds]);
    }

    public function addPreviousLevelCourses(User $student, Request $request)
    {
        abort_unless($student->dashboardRole() === 'student', 404);
        $data = $request->validate([
            'session' => ['required', 'exists:academic_sessions,name'],
            'department_id' => ['required', 'exists:departments,id'],
            'level' => ['required', 'in:100,200,300,400,500,600'],
            'semester' => ['required', 'in:First,Second'],
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['required', 'integer', 'distinct', 'exists:courses,id'],
        ]);
        $registrationSession = AcademicSession::currentName();
        if (! $registrationSession) {
            return back()->withInput()->withErrors(['session' => 'There is no active academic session to register this course into.']);
        }
        $studentLevel = \App\Services\Academic\StudentSessionLevel::find($student, $registrationSession);
        if (! $studentLevel || (int) $data['level'] >= (int) $studentLevel) {
            return back()->withInput()->withErrors(['level' => "Choose a level below the student's level for the selected session."]);
        }
        $courses = Courses::whereIn('id', $data['course_ids'])->where('department_id', $data['department_id'])
            ->forAcademicSession($data['session'])->whereRaw('TRIM(level) = ?', [(string) $data['level']])
            ->where('semester', $data['semester'])->get()->keyBy('id');
        if ($courses->count() !== count($data['course_ids'])) {
            return back()->withInput()->withErrors(['course_ids' => 'Select only courses from the immediately previous level, department, session and semester.']);
        }

        $failedResults = collect();
        foreach ($courses as $course) {
            $failure = \App\Models\Result::where('user_id', $student->id)->where('department_id', $course->department_id)
                ->where('course_code', $course->code)->where('semester', $data['semester'])
                ->where('workflow_status', 'published')->where('outcome_status', 'graded')
                ->where('session', '<', $registrationSession)->orderByDesc('session')->orderByDesc('attempt_number')->first();
            $failedResults->put($course->id, $failure && (float) $failure->grade_point === 0.0 ? $failure : null);
        }

        $added = DB::transaction(function () use ($student, $request, $data, $courses, $failedResults, $registrationSession) {
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $added = 0;
            foreach ($courses as $course) {
                $existing = CourseRegistration::where('user_id', $student->id)->where('course_id', $course->id)
                    ->where('session', $registrationSession)->where('semester', $data['semester'])->first();
                if ($existing) {
                    $failure = $failedResults->get($course->id);
                    if ($existing->is_carryover && (int) $existing->previous_result_id === (int) $failure?->id) continue;
                    if ($existing->previous_result_id && $failure && (int) $existing->previous_result_id !== (int) $failure->id) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['course_ids' => "{$course->code} is already linked to a different failed result. Review the registration before changing its carryover link."]);
                    }
                    $existing->update(['previous_result_id' => $failure?->id ?? $existing->previous_result_id, 'is_carryover' => true, 'acted_by' => $request->user()->id]);
                    ActivityLogger::log($request->user(), 'carryover_registered', "Marked {$student->name}'s {$course->code} registration as a carryover", [
                        'subject' => $existing, 'target_user' => $student, 'department_id' => $course->department_id,
                        'properties' => ['course_id' => $course->id, 'course_code' => $course->code, 'session' => $registrationSession, 'course_catalog_session' => $data['session'], 'semester' => $data['semester'], 'previous_result_id' => $failure?->id, 'status' => $existing->status],
                    ]);
                    $added++;
                    continue;
                }
                $misplaced = CourseRegistration::where('user_id', $student->id)->where('course_id', $course->id)
                    ->where(function ($query) use ($course, $failedResults) {
                        $query->where('is_carryover', true);
                        if ($failedResults->get($course->id)) {
                            $query->orWhere('previous_result_id', $failedResults->get($course->id)->id);
                        }
                    })
                    ->where('semester', $data['semester'])->where('session', '!=', $registrationSession)
                    ->latest('id')->first();
                if ($misplaced && ! $misplaced->results()->exists()) {
                    app(\App\Services\TuitionBilling::class)->assertCleared($student, $registrationSession, $data['semester']);
                    $fromSession = $misplaced->session;
                    $misplaced->update(['session' => $registrationSession, 'is_carryover' => true, 'acted_by' => $request->user()->id]);
                    ActivityLogger::log($request->user(), 'carryover_registration_session_corrected', "Moved {$student->name}'s {$course->code} carryover registration to {$registrationSession}", [
                        'subject' => $misplaced, 'target_user' => $student, 'department_id' => $course->department_id,
                        'properties' => ['course_id' => $course->id, 'course_code' => $course->code, 'from_session' => $fromSession, 'session' => $registrationSession, 'semester' => $data['semester'], 'previous_result_id' => $misplaced->previous_result_id],
                    ]);
                    $added++;
                    continue;
                }
                app(\App\Services\TuitionBilling::class)->assertCleared($student, $registrationSession, $data['semester']);
                $registration = CourseRegistration::create([
                    'user_id' => $student->id, 'acted_by' => $request->user()->id, 'course_id' => $course->id,
                    'previous_result_id' => $failedResults->get($course->id)?->id, 'is_carryover' => true,
                    'semester' => $data['semester'], 'session' => $registrationSession, 'registration_date' => now(), 'status' => 'registered',
                ]);
                ActivityLogger::log($request->user(), 'carryover_registered', "Registered {$student->name} for carryover {$course->code} ({$data['semester']} Semester, {$registrationSession})", [
                    'subject' => $registration, 'target_user' => $student, 'department_id' => $course->department_id,
                    'properties' => ['course_id' => $course->id, 'course_code' => $course->code, 'department_id' => $course->department_id, 'session' => $registrationSession, 'course_catalog_session' => $data['session'], 'semester' => $data['semester'], 'level' => $course->level, 'previous_result_id' => $failedResults->get($course->id)?->id, 'status' => 'registered'],
                ]);
                $added++;
            }
            return $added;
        });

        return redirect()->route('admin.course-registrations.show', ['student' => $student->id, 'session' => $registrationSession, 'semester' => $data['semester']])
            ->with('success', "Added {$added} previous-level course registration(s) for {$student->name}.");
    }

    public function addCarryovers(User $student, Request $request)
    {
        abort_unless($student->dashboardRole() === 'student', 404);
        $data = $request->validate([
            'session' => ['required', 'exists:academic_sessions,name'],
            'semester' => ['required', 'in:First,Second'],
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['required', 'integer', 'distinct', 'exists:courses,id'],
        ]);
        $available = \App\Services\Academic\CarryoverRegistration::available($student, $data['session'])
            ->where('semester', $data['semester'])->keyBy('id');
        if (collect($data['course_ids'])->diff($available->keys())->isNotEmpty()) {
            return back()->withInput()->withErrors(['course_ids' => 'One or more selected courses are not outstanding carryovers for this student and session.']);
        }

        $added = DB::transaction(function () use ($student, $request, $data, $available) {
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $added = 0;
            foreach ($data['course_ids'] as $courseId) {
                $course = $available->get($courseId);
                $failedResult = $course->failedResult;
                $existing = CourseRegistration::where('user_id', $student->id)->where('course_id', $course->id)
                    ->where('session', $data['session'])->where('semester', $data['semester'])->first();
                if ($existing) {
                    if ($existing->previous_result_id === $failedResult->id) continue;
                    throw \Illuminate\Validation\ValidationException::withMessages(['course_ids' => "{$course->code} already has a registration in this session. Review that registration before adding it as a carryover."]);
                }
                app(\App\Services\TuitionBilling::class)->assertCleared($student, $data['session'], $data['semester']);
                $registration = CourseRegistration::create([
                    'user_id' => $student->id, 'acted_by' => $request->user()->id, 'course_id' => $course->id,
                    'previous_result_id' => $failedResult->id, 'is_carryover' => true, 'semester' => $data['semester'], 'session' => $data['session'],
                    'registration_date' => now(), 'status' => 'registered',
                ]);
                ActivityLogger::log($request->user(), 'carryover_registered', "Registered {$student->name} for carryover {$course->code} ({$data['semester']} Semester, {$data['session']})", [
                    'subject' => $registration, 'target_user' => $student, 'department_id' => $student->department_id,
                    'properties' => ['course_id' => $course->id, 'course_code' => $course->code, 'session' => $data['session'], 'semester' => $data['semester'], 'previous_result_id' => $failedResult->id, 'status' => 'registered'],
                ]);
                $added++;
            }
            return $added;
        });

        return redirect()->route('admin.course-registrations.edit', ['student' => $student->id, 'session' => $data['session'], 'semester' => $data['semester']])
            ->with('success', "Added {$added} carryover course registration(s) for {$student->name}.");
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
