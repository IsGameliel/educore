<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\CourseRegistration;
use App\Models\Courses;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminCourseRegistrationController extends Controller
{
    private $creditUnitLimits = [
        '100' => 24,
        '200' => 24,
        '300' => 24,
        '400' => 24,
    ];

    private function normalizeSemester($semester)
    {
        $semester = strtolower(trim((string) $semester));

        if (in_array($semester, ['1', 'first', 'first semester'])) return 'First';
        if (in_array($semester, ['2', 'second', 'second semester'])) return 'Second';

        return 'First';
    }

    private function getCreditUnitLimitForLevel($level)
    {
        return $this->creditUnitLimits[$level] ?? 30;
    }

    public function index(Request $request)
    {
        $currentSession = $request->query('session', $this->getCurrentAcademicSession());
        $academicSessions = $this->getAcademicSessionOptions([$currentSession]);

        $students = User::query()
            ->where('usertype', 'student')
            ->when($request->filled('department_id'), fn($q) => $q->where('department_id', $request->department_id))
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

        return view('admin.course_registrations.index', compact('students', 'currentSession', 'academicSessions'));
    }

    public function show(User $student, Request $request)
    {
        $semester = $this->normalizeSemester($request->query('semester', 'First'));
        $session = $request->query('session', $this->getCurrentAcademicSession());
        $academicSessions = $this->getAcademicSessionOptions([$session]);

        $registrations = CourseRegistration::with('course')
            ->where('user_id', $student->id)
            ->where('semester', $semester)
            ->where('session', $session)
            ->get();

        $totalCredits = $registrations->sum(fn ($registration) => $registration->course?->credit_unit ?? 0);

        return view('admin.course_registrations.show', compact(
            'student',
            'semester',
            'session',
            'academicSessions',
            'registrations',
            'totalCredits'
        ));
    }

    public function edit(User $student, Request $request)
    {
        $semester = $this->normalizeSemester($request->query('semester', 'First'));
        $session = $request->query('session', $this->getCurrentAcademicSession());
        $academicSessions = $this->getAcademicSessionOptions([$session]);

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
            ->where('level', $student->level)
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
        ));
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
        $courseIds = $data['course_ids'] ?? [];

        $courses = Courses::whereIn('id', $courseIds)
            ->where('department_id', $student->department_id)
            ->where('level', $student->level)
            ->where('semester', $semester)
            ->with('prerequisites')
            ->get();

        if ($courses->count() !== count($courseIds)) {
            return back()->withErrors([
                'course_ids' => 'One or more selected courses are invalid for this student/semester.',
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

        $limit = $this->getCreditUnitLimitForLevel($student->level);
        $newTotal = $courses->sum('credit_unit');

        if ($newTotal > $limit) {
            return back()->withErrors([
                'course_ids' => "Credit unit limit exceeded. Max allowed is {$limit}. Selected is {$newTotal}.",
            ]);
        }

        DB::transaction(function () use ($student, $semester, $session, $courseIds, $data, $actor) {
            $existingRegistrations = CourseRegistration::with('course')
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

                CourseRegistration::where('user_id', $student->id)
                    ->where('semester', $semester)
                    ->where('session', $session)
                    ->whereIn('course_id', $toDelete)
                    ->delete();
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
