@php
    $routeName = request()->route()?->getName() ?? '';
    $link = static fn ($label, $route, $patterns, $icon = null) => [
        'label' => $label,
        'url' => route($route),
        'active' => collect($patterns)->contains(fn ($pattern) => \Illuminate\Support\Str::is($pattern, $routeName)),
        'icon' => $icon,
    ];
    $sections = [
        'Administration' => [
            ['label' => 'Students', 'icon' => 'mdi-account-school', 'id' => 'students-menu', 'children' => [
                $link('Manage Students', 'admin.students.index', ['admin.students.*']),
                $link('Admitted Students', 'admin.admitted-students.index', ['admin.admitted-students.*']),
                $link('Course Registrations', 'admin.course-registrations.index', ['admin.course-registrations.*']),
            ]],
            $link('Manage Staff', 'admin.staffs.index', ['admin.staffs.*'], 'mdi-account-key'),
        ],
        'Academics' => [
            ['label' => 'Academic Setup', 'icon' => 'mdi-library-shelves', 'id' => 'academic-menu', 'children' => [
                ['label' => 'Academic Sessions', 'url' => route('dashboard').'#academic-sessions-panel', 'active' => false],
                $link('Faculties', 'admin.faculties.index', ['admin.faculties.*']),
                array_merge($link('Departments', 'admin.departments.index', ['admin.departments.*']), [
                    'active' => str_starts_with($routeName, 'admin.departments.') && !str_starts_with($routeName, 'admin.departments.passmarks'),
                ]),
                $link('Courses', 'admin.courses.index', ['admin.courses.*']),
                $link('Pass Marks', 'admin.departments.passmarks', ['admin.departments.passmarks*']),
                $link('Grading Policies', 'academic.policies', ['academic.policies*']),
            ]],
            ['label' => 'Teaching & Tests', 'icon' => 'mdi-human-male-board', 'id' => 'delivery-menu', 'children' => [
                $link('Class Schedules', 'admin.class-schedules.index', ['admin.class-schedules.*']),
                $link('Attendance', 'admin.attendance.index', ['admin.attendance.*']),
                $link('Lecture Materials', 'admin.course-materials.index', ['admin.course-materials.*']),
                $link('Tests', 'admin.tests.index', ['admin.tests.*']),
            ]],
        ],
        'Results & Records' => [
            ['label' => 'Results', 'icon' => 'mdi-clipboard-text', 'id' => 'assessment-menu', 'children' => [
                $link('Review & Publish', 'academic.index', ['academic.index', 'academic.show', 'academic.update', 'academic.transition', 'academic.batch', 'academic.resit', 'academic.correction*']),
                $link('Enter Results', 'academic.entry', ['academic.entry', 'academic.results.store', 'academic.results.students', 'admin.results.create', 'admin.results.store']),
                $link('Upload Results', 'academic.upload', ['academic.upload', 'academic.results.storeUpload', 'academic.results.template.*', 'admin.results.upload', 'admin.results.storeUpload']),
                $link('Student Results', 'admin.results.index', ['admin.results.index', 'admin.results.show', 'admin.results.edit', 'admin.results.update']),
            ]],
            $link('Result Appeals', 'academic.appeals', ['academic.appeals*'], 'mdi-comment-alert-outline'),
            $link('Transcripts', 'academic.transcripts', ['academic.transcripts*'], 'mdi-file-document'),
            $link('Academic Reports', 'academic.reports', ['academic.reports*'], 'mdi-chart-bar'),
        ],
        'System & Account' => [
            $link('Backup & Restore', 'admin.backups.index', ['admin.backups.*'], 'mdi-database'),
            $link('My Profile', 'profile.show', ['profile.*'], 'mdi-account-circle'),
        ],
    ];
@endphp

<style>
    #sidebar .nav .admin-section-heading {
        margin: 1.5rem 2.25rem 0.75rem;
        padding: 0;
        list-style: none;
    }

    #sidebar .nav .admin-section-heading + .nav-item {
        margin-top: 0.15rem;
    }

    #sidebar .nav .admin-section-heading__label {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        color: #7b8190;
        font-family: "ubuntu-medium", sans-serif;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        line-height: 1;
        text-transform: uppercase;
    }

    #sidebar .nav .admin-section-heading__label::before {
        content: "";
        flex: 0 0 1.4rem;
        height: 2px;
        border-radius: 999px;
        background: linear-gradient(90deg, #b66dff, #d8b4fe);
    }

    #sidebar .nav .admin-section-heading__label::after {
        content: "";
        flex: 1 1 auto;
        height: 1px;
        background: #ececf4;
    }

    .sidebar-icon-only #sidebar .nav .admin-section-heading {
        display: none;
    }
</style>

<nav class="sidebar sidebar-offcanvas" id="sidebar" aria-label="Admin navigation" data-route-navigation="true">
    <ul class="nav">
        <li class="nav-item nav-profile">
            <a href="{{ route('profile.show') }}" class="nav-link">
                <div class="nav-profile-image">
                    <img src="{{ Auth::user()->profile_photo_url }}" alt="profile" />
                    <span class="login-status online"></span>
                </div>
                <div class="nav-profile-text d-flex flex-column">
                    <span class="font-weight-bold mb-2">{{ Auth::user()->name }}</span>
                    <span class="text-secondary text-small text-capitalize">{{ str_replace('_', ' ', Auth::user()->usertype) }}</span>
                </div>
                <i class="mdi mdi-bookmark-check text-success nav-profile-badge"></i>
            </a>
        </li>


        <li class="nav-item {{ $routeName === 'dashboard' ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('dashboard') }}" @if($routeName === 'dashboard') aria-current="page" @endif>
                <span class="menu-title">Dashboard</span>
                <i class="mdi mdi-home menu-icon"></i>
            </a>
        </li>

        @foreach($sections as $heading => $items)
            <li class="admin-section-heading">
                <span class="admin-section-heading__label">{{ $heading }}</span>
            </li>
            @foreach($items as $item)
                @php
                    $hasChildren = isset($item['children']);
                    $isActive = $hasChildren ? collect($item['children'])->contains('active', true) : $item['active'];
                @endphp
                <li class="nav-item {{ $isActive ? 'active' : '' }}">
                    @if($hasChildren)
                        <a class="nav-link" data-bs-toggle="collapse" href="#{{ $item['id'] }}"
                           aria-expanded="{{ $isActive ? 'true' : 'false' }}" aria-controls="{{ $item['id'] }}">
                            <span class="menu-title">{{ $item['label'] }}</span>
                            <i class="menu-arrow"></i>
                            <i class="mdi {{ $item['icon'] }} menu-icon"></i>
                        </a>
                        <div class="collapse {{ $isActive ? 'show' : '' }}" id="{{ $item['id'] }}">
                            <ul class="nav flex-column sub-menu">
                                @foreach($item['children'] as $child)
                                    <li class="nav-item">
                                        <a class="nav-link {{ $child['active'] ? 'active' : '' }}" href="{{ $child['url'] }}"
                                           @if($child['active']) aria-current="page" @endif>{{ $child['label'] }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <a class="nav-link" href="{{ $item['url'] }}" @if($isActive) aria-current="page" @endif>
                            <span class="menu-title">{{ $item['label'] }}</span>
                            <i class="mdi {{ $item['icon'] }} menu-icon"></i>
                        </a>
                    @endif
                </li>
            @endforeach
        @endforeach

        <li class="nav-item mt-3">
            <a
                class="nav-link"
                href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            >
                <span class="menu-title">Logout</span>
                <i class="mdi mdi-logout menu-icon"></i>
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </li>
    </ul>
</nav>
