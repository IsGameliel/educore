<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\CourseMaterial;
use App\Models\Courses;
use App\Models\ClassSchedule;
use App\Models\ActivityLog;
use App\Models\AcademicSession;
use App\Models\DashboardProject;
use App\Models\DashboardTodo;
use App\Support\StudentUpdateFeed;

class HomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->usertype == 'student') {
            // Ensure the user has a department assigned
            if (!$user->department) {
                return redirect()->back()->with('error', 'No department assigned to the student.');
            }

            // Fetch course materials and courses
            $courseMaterialsCount = CourseMaterial::where('level', $user->level)
                ->where('department_id', $user->department_id)
                ->count();

            $courseCount = Courses::where('department_id', $user->department_id)
                ->where('level', $user->level)
                ->count();

            // Fetch class schedules
            $schedules = ClassSchedule::with('lecturer')
                ->where('department_id', $user->department->id)
                ->get()
                ->map(function ($schedule) {
                    $course = Courses::find($schedule->subject); // Assuming subject is a course ID
                    $schedule->subject = $course ? $course->title : 'Unknown Course'; // Replace ID with title
                    return $schedule;
                });

            $recentUpdates = StudentUpdateFeed::forUser($user);

            return view('student.dashboard', compact('courseMaterialsCount', 'courseCount', 'schedules', 'recentUpdates') + $this->getDashboardWidgets($user));
        }

        elseif ($user->usertype == 'admin') {
            return view('admin.dashboard', $this->buildDashboardPayload($user));
        }
        elseif ($user->usertype == 'lecturer') {
            return view('lecturer.dashboard', $this->buildDashboardPayload($user));
        }

        // Default view for other user types
        return redirect('/')->with('error', 'Unauthorized access.');
    }

    private function buildDashboardPayload(User $user): array
    {
        $studentsCount = User::where('usertype', 'student')->count();
        $lecturersCount = User::where('usertype', 'lecturer')->count();
        $adminsCount = User::where('usertype', 'admin')->count();
        $totalUsersCount = User::count();
        $departmentsCount = Department::count();
        $facultyCount = Faculty::count();
        $academicSessions = AcademicSession::query()
            ->orderByDesc('start_year')
            ->get();
        $departmentCourseSummary = Department::withCount('courses')
            ->orderBy('name')
            ->get(['id', 'name']);
        $visitorStatistics = collect([
            [
                'label' => 'Students',
                'count' => $studentsCount,
                'color' => 'danger',
            ],
            [
                'label' => 'Lecturers',
                'count' => $lecturersCount,
                'color' => 'info',
            ],
            [
                'label' => 'Administrators',
                'count' => $adminsCount,
                'color' => 'success',
            ],
        ])->map(function ($stat) use ($totalUsersCount) {
            $stat['percentage'] = $totalUsersCount > 0
                ? round(($stat['count'] / $totalUsersCount) * 100, 1)
                : 0;

            return $stat;
        });

        return compact(
            'studentsCount',
            'lecturersCount',
            'adminsCount',
            'totalUsersCount',
            'departmentsCount',
            'facultyCount',
            'academicSessions',
            'departmentCourseSummary',
            'visitorStatistics'
        ) + $this->getDashboardWidgets($user) + [
            'recentActivities' => $this->getRecentActivities($user),
        ];
    }

    private function getDashboardWidgets(User $user): array
    {
        return [
            'dashboardTodos' => DashboardTodo::where('user_id', $user->id)
                ->latest()
                ->get(),
            'dashboardProjects' => DashboardProject::where('user_id', $user->id)
                ->orderByRaw('due_date IS NULL')
                ->orderBy('due_date')
                ->latest('id')
                ->get(),
        ];
    }

    private function getRecentActivities(User $user)
    {
        $query = ActivityLog::with(['actor', 'targetUser'])->latest();

        if ($user->usertype === 'lecturer') {
            $departmentIds = $user->assignedCourses()
                ->pluck('courses.department_id')
                ->filter()
                ->unique()
                ->values();

            $query->where(function ($activityQuery) use ($user, $departmentIds) {
                $activityQuery->where('actor_id', $user->id);

                if ($departmentIds->isNotEmpty()) {
                    $activityQuery->orWhereIn('department_id', $departmentIds);
                }
            });
        }

        return $query
            ->take(8)
            ->get()
            ->map(function ($activity) {
                $meta = $this->getActivityMeta($activity->action);

                return [
                    'actor' => $activity->actor?->name ?? 'System',
                    'activity' => $meta['label'],
                    'details' => $activity->description,
                    'status' => $meta['status'],
                    'status_color' => $meta['color'],
                    'occurred_at' => $activity->created_at,
                ];
            });
    }

    private function getActivityMeta(string $action): array
    {
        return match ($action) {
            'course_created' => ['label' => 'Course Update', 'status' => 'Added', 'color' => 'info'],
            'course_updated' => ['label' => 'Course Update', 'status' => 'Changed', 'color' => 'warning'],
            'material_uploaded' => ['label' => 'Lecture Material', 'status' => 'Uploaded', 'color' => 'primary'],
            'material_updated' => ['label' => 'Lecture Material', 'status' => 'Updated', 'color' => 'warning'],
            'test_published' => ['label' => 'Test Notification', 'status' => 'Published', 'color' => 'danger'],
            'test_updated' => ['label' => 'Test Notification', 'status' => 'Updated', 'color' => 'warning'],
            'result_uploaded' => ['label' => 'Result Published', 'status' => 'Published', 'color' => 'success'],
            'result_updated' => ['label' => 'Result Updated', 'status' => 'Updated', 'color' => 'warning'],
            'pass_mark_updated' => ['label' => 'Pass Mark Updated', 'status' => 'Updated', 'color' => 'warning'],
            'registration_created' => ['label' => 'Course Registration', 'status' => 'Registered', 'color' => 'info'],
            'registration_approved' => ['label' => 'Registration Approval', 'status' => 'Approved', 'color' => 'success'],
            'registration_rejected' => ['label' => 'Registration Review', 'status' => 'Rejected', 'color' => 'danger'],
            'registration_withdrawn' => ['label' => 'Registration Change', 'status' => 'Withdrawn', 'color' => 'warning'],
            'registration_removed' => ['label' => 'Registration Change', 'status' => 'Removed', 'color' => 'danger'],
            default => ['label' => 'Activity', 'status' => 'Updated', 'color' => 'secondary'],
        };
    }

}
