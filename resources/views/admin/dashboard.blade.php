@extends('layouts.dash')

@section('content')
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                  <i class="mdi mdi-home"></i>
                </span> Dashboard
                    </h3>
                    <nav aria-label="breadcrumb">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item active" aria-current="page">
                                <span></span>Overview <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                            </li>
                        </ul>
                    </nav>
                </div>
                <div class="row">
                    <div class="col-md-4 stretch-card grid-margin">
                        <div class="card bg-gradient-danger card-img-holder text-white">
                            <div class="card-body">
                                <img src="{{ asset('dash/assets/images/dashboard/circle.svg')}}" class="card-img-absolute" alt="circle-image" />
                                <h4 class="font-weight-normal mb-3">Students <i class="mdi mdi-account-group menu-icon mdi-24px float-end"></i>
                                </h4>
                                <h2 class="mb-5">{{ $studentsCount}}</h2>
                                <h6 class="card-text">Registered student accounts</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 stretch-card grid-margin">
                        <div class="card bg-gradient-info card-img-holder text-white">
                            <div class="card-body">
                                <img src="dash/assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                                <h4 class="font-weight-normal mb-3">Lecturers <i class="mdi mdi-school mdi-24px float-end"></i>
                                </h4>
                                <h2 class="mb-5">{{ $lecturersCount }}</h2>
                                <h6 class="card-text">Registered lecturer accounts</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 stretch-card grid-margin">
                        <div class="card bg-gradient-success card-img-holder text-white">
                            <div class="card-body">
                                <img src="dash/assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                                <h4 class="font-weight-normal mb-3">Administrators <i class="mdi mdi-shield-account mdi-24px float-end"></i>
                                </h4>
                                <h2 class="mb-5">{{ $adminsCount }}</h2>
                                <h6 class="card-text">Accounts managing the platform</h6>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-7 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <div class="clearfix">
                                    <h4 class="card-title float-start">App Visitor Statistics</h4>
                                    <div class="text-muted float-end">Total Accounts: {{ $totalUsersCount }}</div>
                                </div>
                                <canvas id="app-visitor-chart" class="mt-4"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Platform Overview</h4>
                                <div class="table-responsive">
                                    <table class="table">
                                        <tbody>
                                        <tr>
                                            <td>Total Users</td>
                                            <td class="text-end font-weight-bold">{{ $totalUsersCount }}</td>
                                        </tr>
                                        <tr>
                                            <td>Students</td>
                                            <td class="text-end font-weight-bold">{{ $studentsCount }}</td>
                                        </tr>
                                        <tr>
                                            <td>Lecturers</td>
                                            <td class="text-end font-weight-bold">{{ $lecturersCount }}</td>
                                        </tr>
                                        <tr>
                                            <td>Administrators</td>
                                            <td class="text-end font-weight-bold">{{ $adminsCount }}</td>
                                        </tr>
                                        <tr>
                                            <td>Departments</td>
                                            <td class="text-end font-weight-bold">{{ $departmentsCount }}</td>
                                        </tr>
                                        <tr>
                                            <td>Faculties</td>
                                            <td class="text-end font-weight-bold">{{ $facultyCount }}</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 grid-margin" id="academic-sessions-panel">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                                    <div>
                                        <h4 class="card-title mb-1">Academic Sessions</h4>
                                        <p class="text-muted mb-0">Create, update, and choose the active session students will use for course registration.</p>
                                        <div class="mt-2">
                                            <span class="badge badge-gradient-primary">
                                                Active Session: {{ $currentAcademicSession?->name ?? 'Not set' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                @if ($errors->has('name'))
                                    <div class="alert alert-danger">
                                        {{ $errors->first('name') }}
                                    </div>
                                @endif

                                <div class="row g-4">
                                    <div class="col-lg-4">
                                        <div class="border rounded p-3 h-100">
                                            <h5 class="mb-3">Create Session</h5>
                                            <form method="POST" action="{{ route('admin.academic-sessions.store') }}">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="session_name" class="form-label">Session</label>
                                                    <input
                                                        type="text"
                                                        name="name"
                                                        id="session_name"
                                                        class="form-control"
                                                        value="{{ old('name') }}"
                                                        placeholder="e.g. 2021/2022"
                                                        required
                                                    >
                                                    <small class="text-muted">Use consecutive years only.</small>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Create Session</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="col-lg-8">
                                        <div class="border rounded p-3 h-100">
                                            <h5 class="mb-3">Existing Sessions</h5>
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0">
                                                    <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Session</th>
                                                        <th>Status</th>
                                                        <th>Update</th>
                                                        <th>Activation</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @forelse ($academicSessions as $academicSession)
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td class="fw-semibold">{{ $academicSession->name }}</td>
                                                            <td>
                                                                @if($academicSession->is_active)
                                                                    <span class="badge badge-gradient-success">Active</span>
                                                                @else
                                                                    <span class="badge badge-light">Inactive</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <form method="POST" action="{{ route('admin.academic-sessions.update', $academicSession) }}" class="row g-2 align-items-center">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <div class="col-md-8">
                                                                        <input
                                                                            type="text"
                                                                            name="name"
                                                                            class="form-control"
                                                                            value="{{ old('name_' . $academicSession->id, $academicSession->name) }}"
                                                                            placeholder="e.g. 2021/2022"
                                                                            required
                                                                        >
                                                                    </div>
                                                                    <div class="col-md-4 d-grid">
                                                                        <button type="submit" class="btn btn-outline-primary btn-sm">Save</button>
                                                                    </div>
                                                                </form>
                                                            </td>
                                                            <td>
                                                                @if($academicSession->is_active)
                                                                    <button type="button" class="btn btn-sm btn-success" disabled>Current Session</button>
                                                                @else
                                                                    <form method="POST" action="{{ route('admin.academic-sessions.activate', $academicSession) }}">
                                                                        @csrf
                                                                        <button type="submit" class="btn btn-outline-primary btn-sm">Make Active</button>
                                                                    </form>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center text-muted">No academic sessions created yet.</td>
                                                        </tr>
                                                    @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 grid-margin">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="card-title mb-0">Department Course Allocation</h4>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="text-muted small">{{ $departmentCourseSummary->count() }} departments</span>
                                        <button
                                            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#department-course-allocation"
                                            aria-expanded="true"
                                            aria-controls="department-course-allocation"
                                        >
                                            <i class="mdi mdi-chevron-up" data-collapse-icon></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="collapse show" id="department-course-allocation">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                            <tr>
                                                <th>Department</th>
                                                <th class="text-end">Assigned Courses</th>
                                            </tr>
                                            </thead>
                                            <tbody id="department-course-table-body">
                                            @forelse ($departmentCourseSummary as $department)
                                                <tr class="department-course-row">
                                                    <td>{{ $department->name }}</td>
                                                    <td class="text-end font-weight-bold">{{ $department->courses_count }}</td>
                                                </tr>
                                            @empty
                                                <tr id="department-course-empty-row">
                                                    <td colspan="2" class="text-center text-muted">No departments found.</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    @if ($departmentCourseSummary->isNotEmpty())
                                        <div class="d-flex justify-content-between align-items-center mt-3 gap-3 flex-wrap">
                                            <small class="text-muted" id="department-course-pagination-status">
                                                Showing 1-1 of {{ $departmentCourseSummary->count() }} departments
                                            </small>
                                            <div class="d-flex gap-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    id="department-course-prev"
                                                >
                                                    Previous
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    id="department-course-next"
                                                >
                                                    Next
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 grid-margin">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="card-title mb-0">Recent Activity</h4>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="text-muted small">Result uploads and course registrations</span>
                                        <button
                                            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#recent-activity-table"
                                            aria-expanded="true"
                                            aria-controls="recent-activity-table"
                                        >
                                            <i class="mdi mdi-chevron-up" data-collapse-icon></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="collapse show" id="recent-activity-table">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th> User </th>
                                                <th> Activity </th>
                                                <th> Details </th>
                                                <th> Status </th>
                                                <th> Time </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse ($recentActivities as $activity)
                                                <tr>
                                                    <td>{{ $activity['actor'] }}</td>
                                                    <td>{{ $activity['activity'] }}</td>
                                                    <td>{{ $activity['details'] }}</td>
                                                    <td>
                                                        <label class="badge badge-gradient-{{ $activity['status_color'] }}">
                                                            {{ $activity['status'] }}
                                                        </label>
                                                    </td>
                                                    <td>
                                                        @if ($activity['occurred_at'])
                                                            {{ $activity['occurred_at']->format('M j, Y g:i A') }}
                                                            <div class="text-muted small">{{ $activity['occurred_at']->diffForHumans() }}</div>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">
                                                        No recent result uploads or course registrations found.
                                                    </td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-5 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body p-0 d-flex">
                                <div id="inline-datepicker" class="datepicker datepicker-custom"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Recent Updates</h4>
                                <div class="d-flex">
                                    <div class="d-flex align-items-center me-4 text-muted font-weight-light">
                                        <i class="mdi mdi-account-outline icon-sm me-2"></i>
                                        <span>jack Menqu</span>
                                    </div>
                                    <div class="d-flex align-items-center text-muted font-weight-light">
                                        <i class="mdi mdi-clock icon-sm me-2"></i>
                                        <span>October 3rd, 2018</span>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-6 pe-1">
                                        <img src="assets/images/dashboard/img_1.jpg" class="mb-2 mw-100 w-100 rounded" alt="image">
                                        <img src="assets/images/dashboard/img_4.jpg" class="mw-100 w-100 rounded" alt="image">
                                    </div>
                                    <div class="col-6 ps-1">
                                        <img src="assets/images/dashboard/img_2.jpg" class="mb-2 mw-100 w-100 rounded" alt="image">
                                        <img src="assets/images/dashboard/img_3.jpg" class="mw-100 w-100 rounded" alt="image">
                                    </div>
                                </div>
                                <div class="d-flex mt-5 align-items-top">
                                    <img src="assets/images/faces/face3.jpg" class="img-sm rounded-circle me-3" alt="image">
                                    <div class="mb-0 flex-grow">
                                        <h5 class="me-2 mb-2">School Website - Authentication Module.</h5>
                                        <p class="mb-0 font-weight-light">It is a long established fact that a reader will be distracted by the readable content of a page.</p>
                                    </div>
                                    <div class="ms-auto">
                                        <i class="mdi mdi-heart-outline text-muted"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @include('dashboard.widgets')
            </div>
            <!-- content-wrapper ends -->
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chartCanvas = document.getElementById('app-visitor-chart');
            const collapseTargets = [
                'department-course-allocation',
                'recent-activity-table',
            ];
            const departmentCourseRows = Array.from(document.querySelectorAll('.department-course-row'));
            const departmentCoursePrevButton = document.getElementById('department-course-prev');
            const departmentCourseNextButton = document.getElementById('department-course-next');
            const departmentCourseStatus = document.getElementById('department-course-pagination-status');
            const departmentCoursesPerPage = 6;
            let departmentCoursePage = 0;

            collapseTargets.forEach(function (targetId) {
                const collapseElement = document.getElementById(targetId);
                const collapseButton = document.querySelector('[data-bs-target="#' + targetId + '"]');
                const collapseIcon = collapseButton?.querySelector('[data-collapse-icon]');

                if (!collapseElement || !collapseButton || !collapseIcon) {
                    return;
                }

                collapseElement.addEventListener('show.bs.collapse', function () {
                    collapseIcon.classList.remove('mdi-chevron-down');
                    collapseIcon.classList.add('mdi-chevron-up');
                    collapseButton.setAttribute('aria-expanded', 'true');
                });

                collapseElement.addEventListener('hide.bs.collapse', function () {
                    collapseIcon.classList.remove('mdi-chevron-up');
                    collapseIcon.classList.add('mdi-chevron-down');
                    collapseButton.setAttribute('aria-expanded', 'false');
                });
            });

            renderDepartmentCourses();

            if (!chartCanvas) {
                return;
            }

            function renderDepartmentCourses() {
                if (!departmentCourseRows.length) {
                    return;
                }

                const totalPages = Math.ceil(departmentCourseRows.length / departmentCoursesPerPage);
                const startIndex = departmentCoursePage * departmentCoursesPerPage;
                const endIndex = Math.min(startIndex + departmentCoursesPerPage, departmentCourseRows.length);

                departmentCourseRows.forEach(function (row, index) {
                    row.classList.toggle('d-none', index < startIndex || index >= endIndex);
                });

                if (departmentCourseStatus) {
                    departmentCourseStatus.textContent = 'Showing ' + (startIndex + 1) + '-' + endIndex + ' of ' + departmentCourseRows.length + ' departments';
                }

                if (departmentCoursePrevButton) {
                    departmentCoursePrevButton.disabled = departmentCoursePage === 0;
                }

                if (departmentCourseNextButton) {
                    departmentCourseNextButton.disabled = departmentCoursePage >= totalPages - 1;
                }
            }

            departmentCoursePrevButton?.addEventListener('click', function () {
                if (departmentCoursePage === 0) {
                    return;
                }

                departmentCoursePage -= 1;
                renderDepartmentCourses();
            });

            departmentCourseNextButton?.addEventListener('click', function () {
                const totalPages = Math.ceil(departmentCourseRows.length / departmentCoursesPerPage);

                if (departmentCoursePage >= totalPages - 1) {
                    return;
                }

                departmentCoursePage += 1;
                renderDepartmentCourses();
            });

            const visitorStatistics = @json($visitorStatistics);
            const labels = visitorStatistics.map(function (item) {
                return item.label;
            });
            const counts = visitorStatistics.map(function (item) {
                return item.count;
            });
            const percentages = visitorStatistics.map(function (item) {
                return item.percentage;
            });

            new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Users',
                        data: counts,
                        backgroundColor: [
                            'rgba(254, 124, 150, 0.75)',
                            'rgba(54, 185, 204, 0.75)',
                            'rgba(28, 200, 138, 0.75)'
                        ],
                        borderColor: [
                            '#fe7c96',
                            '#36b9cc',
                            '#1cc88a'
                        ],
                        borderWidth: 1,
                        borderRadius: 8,
                        maxBarThickness: 72
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const value = context.raw ?? 0;
                                    const percentage = percentages[context.dataIndex] ?? 0;
                                    return value + ' users (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.08)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection
