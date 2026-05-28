@php
    $routeName = request()->route()?->getName() ?? '';
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

<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        <li class="nav-item nav-profile">
            <a href="#" class="nav-link">
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

        <li class="nav-item">
            <a class="nav-link {{ request()->is('home') ? 'active' : '' }}" href="{{ url('home') }}">
                <span class="menu-title">Dashboard</span>
                <i class="mdi mdi-home menu-icon"></i>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ $routeName === 'profile.show' ? 'active' : '' }}" href="{{ route('profile.show') }}">
                <span class="menu-title">Profile</span>
                <i class="mdi mdi-account-circle menu-icon"></i>
            </a>
        </li>

        <li class="admin-section-heading">
            <span class="admin-section-heading__label">Administration</span>
        </li>

        <li class="nav-item">
            <a
                class="nav-link"
                data-bs-toggle="collapse"
                href="#students-menu"
                aria-expanded="{{ str_starts_with($routeName, 'admin.students.') || str_starts_with($routeName, 'admin.course-registrations.') ? 'true' : 'false' }}"
                aria-controls="students-menu"
            >
                <span class="menu-title">Students</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-account-school menu-icon"></i>
            </a>
            <div class="collapse {{ str_starts_with($routeName, 'admin.students.') || str_starts_with($routeName, 'admin.course-registrations.') ? 'show' : '' }}" id="students-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link {{ str_starts_with($routeName, 'admin.students.') ? 'active' : '' }}" href="{{ route('admin.students.index') }}">
                            Manage Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_starts_with($routeName, 'admin.course-registrations.') ? 'active' : '' }}" href="{{ route('admin.course-registrations.index') }}">
                            Course Registrations
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="nav-item">
            <a
                class="nav-link"
                data-bs-toggle="collapse"
                href="#staff-menu"
                aria-expanded="{{ str_starts_with($routeName, 'admin.staffs.') ? 'true' : 'false' }}"
                aria-controls="staff-menu"
            >
                <span class="menu-title">Staff</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-account-key menu-icon"></i>
            </a>
            <div class="collapse {{ str_starts_with($routeName, 'admin.staffs.') ? 'show' : '' }}" id="staff-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link {{ str_starts_with($routeName, 'admin.staffs.') ? 'active' : '' }}" href="{{ route('admin.staffs.index') }}">
                            Manage Staff
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="admin-section-heading">
            <span class="admin-section-heading__label">Academic Setup</span>
        </li>

        <li class="nav-item">
            <a
                class="nav-link"
                data-bs-toggle="collapse"
                href="#academic-menu"
                aria-expanded="{{ str_starts_with($routeName, 'admin.courses.') || str_starts_with($routeName, 'admin.faculties.') || str_starts_with($routeName, 'admin.departments.') ? 'true' : 'false' }}"
                aria-controls="academic-menu"
            >
                <span class="menu-title">Courses & Structure</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-library-shelves menu-icon"></i>
            </a>
            <div class="collapse {{ str_starts_with($routeName, 'admin.courses.') || str_starts_with($routeName, 'admin.faculties.') || str_starts_with($routeName, 'admin.departments.') ? 'show' : '' }}" id="academic-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link {{ str_starts_with($routeName, 'admin.courses.') ? 'active' : '' }}" href="{{ route('admin.courses.index') }}">
                            Courses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_starts_with($routeName, 'admin.faculties.') ? 'active' : '' }}" href="{{ route('admin.faculties.index') }}">
                            Faculties
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_starts_with($routeName, 'admin.departments.') && $routeName !== 'admin.departments.passmarks' && $routeName !== 'admin.departments.passmarks.update' ? 'active' : '' }}" href="{{ route('admin.departments.index') }}">
                            Departments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $routeName === 'admin.departments.passmarks' || $routeName === 'admin.departments.passmarks.update' ? 'active' : '' }}" href="{{ route('admin.departments.passmarks') }}">
                            Pass Marks
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('home') ? 'active' : '' }}" href="{{ route('dashboard') }}#academic-sessions-panel">
                            Academic Sessions
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="nav-item">
            <a
                class="nav-link"
                data-bs-toggle="collapse"
                href="#delivery-menu"
                aria-expanded="{{ str_starts_with($routeName, 'admin.class-schedules.') || str_starts_with($routeName, 'admin.course-materials.') ? 'true' : 'false' }}"
                aria-controls="delivery-menu"
            >
                <span class="menu-title">Teaching Delivery</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-human-male-board menu-icon"></i>
            </a>
            <div class="collapse {{ str_starts_with($routeName, 'admin.class-schedules.') || str_starts_with($routeName, 'admin.course-materials.') ? 'show' : '' }}" id="delivery-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link {{ str_starts_with($routeName, 'admin.class-schedules.') ? 'active' : '' }}" href="{{ route('admin.class-schedules.index') }}">
                            Class Schedules
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ str_starts_with($routeName, 'admin.course-materials.') ? 'active' : '' }}" href="{{ route('admin.course-materials.index') }}">
                            Lecture Materials
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="admin-section-heading">
            <span class="admin-section-heading__label">Assessment</span>
        </li>

        <li class="nav-item">
            <a
                class="nav-link"
                data-bs-toggle="collapse"
                href="#assessment-menu"
                aria-expanded="{{ str_starts_with($routeName, 'admin.tests.') || str_starts_with($routeName, 'admin.results.') ? 'true' : 'false' }}"
                aria-controls="assessment-menu"
            >
                <span class="menu-title">Tests & Results</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-clipboard-text menu-icon"></i>
            </a>
            <div class="collapse {{ str_starts_with($routeName, 'admin.tests.') || str_starts_with($routeName, 'admin.results.') ? 'show' : '' }}" id="assessment-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link {{ str_starts_with($routeName, 'admin.tests.') ? 'active' : '' }}" href="{{ route('admin.tests.index') }}">
                            Tests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $routeName === 'admin.results.index' ? 'active' : '' }}" href="{{ route('admin.results.index') }}">
                            Results
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $routeName === 'admin.results.create' ? 'active' : '' }}" href="{{ route('admin.results.create') }}">
                            Publish Results
                        </a>
                    </li>
                </ul>
            </div>
        </li>

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
