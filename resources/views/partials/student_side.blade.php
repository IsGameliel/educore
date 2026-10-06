@php
    $routeName = request()->route()?->getName() ?? '';
    $link = static fn ($label, $route, $patterns, $icon = null) => [
        'label' => $label,
        'url' => route($route),
        'active' => collect($patterns)->contains(fn ($pattern) => \Illuminate\Support\Str::is($pattern, $routeName)),
        'icon' => $icon,
    ];
    $sessionName = \App\Models\AcademicSession::currentName()
        ?? (now()->month >= 8 ? now()->year.'/'.(now()->year + 1) : (now()->year - 1).'/'.now()->year);
    $sections = [
        'Academics' => [
            $link('Student Services', 'student.services', ['student.services'], 'mdi-account-school'),
            $link('Academic Assistance', 'academic.assistance', ['academic.assistance'], 'mdi-clipboard-check'),
            ['label' => 'Courses', 'icon' => 'mdi-book-open-page-variant', 'id' => 'student-courses-menu', 'children' => [
                $link('Course Registration', 'student.courses.registration', ['student.courses.registration', 'student.courses.register', 'student.courses.byLevel']),
                ['label' => 'Registered Courses',
                 'url' => route('student.courses.registered', ['semester' => 'First', 'session' => $sessionName]),
                 'active' => request()->routeIs('student.courses.registered', 'student.courses.withdraw', 'student.courses.queue', 'student.courses.download.*')],
                $link('Course Materials', 'student.course-materials', ['student.course-materials']),
            ]],
            $link('Class Timetable', 'student.schedule', ['student.schedule'], 'mdi-calendar-clock'),
            $link('Tests', 'student.tests.index', ['student.tests.*'], 'mdi-pencil-box-outline'),
        ],
        'Results & Requests' => [
            ['label' => 'Results', 'icon' => 'mdi-clipboard-text', 'id' => 'student-results-menu', 'children' => [
                $link('View Results', 'student.results.index', ['student.results.*']),
                $link('Academic Records', 'academic.index', ['academic.index', 'academic.show']),
            ]],
            $link('Result Appeals', 'academic.appeals', ['academic.appeals*'], 'mdi-comment-alert-outline'),
            $link('Transcript Requests', 'academic.transcripts', ['academic.transcripts*', 'documents.transcripts.*'], 'mdi-file-document'),
        ],
        'Fees & Payments' => [
            $link('Tuition & Balance', 'tuition.index', ['tuition.*'], 'mdi-school'),
            $link('Payment History', 'payments.index', ['payments.index', 'payments.show', 'payments.checkout', 'payments.refresh'], 'mdi-wallet'),
            $link('Receipts', 'payments.receipts', ['payments.receipts', 'payments.receipt'], 'mdi-receipt'),
        ],
        'Account' => [
            $link('Temporary ID Card', 'student.id-card.index', ['student.id-card.*'], 'mdi-card-account-details-outline'),
            $link('My Profile', 'profile.show', ['profile.*', 'student.profile.*'], 'mdi-account-circle'),
        ],
    ];
@endphp
<style>
    #sidebar .nav .student-section-heading {
        margin: 1.5rem 2.25rem 0.75rem;
        padding: 0;
        list-style: none;
    }

    #sidebar .nav .student-section-heading + .nav-item {
        margin-top: 0.15rem;
    }

    #sidebar .nav .student-section-heading__label {
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

    #sidebar .nav .student-section-heading__label::before {
        content: "";
        flex: 0 0 1.4rem;
        height: 2px;
        border-radius: 999px;
        background: linear-gradient(90deg, #b66dff, #d8b4fe);
    }

    #sidebar .nav .student-section-heading__label::after {
        content: "";
        flex: 1 1 auto;
        height: 1px;
        background: #ececf4;
    }

    .sidebar-icon-only #sidebar .nav .student-section-heading {
        display: none;
    }
</style>

<nav class="sidebar sidebar-offcanvas" id="sidebar" aria-label="Student navigation" data-route-navigation="true">
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
            <a class="nav-link {{ $routeName === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}" @if($routeName === 'dashboard') aria-current="page" @endif>
                <span class="menu-title">Dashboard</span>
                <i class="mdi mdi-home menu-icon"></i>
            </a>
        </li>

        @foreach($sections as $heading => $items)
            <li class="student-section-heading">
                <span class="student-section-heading__label">{{ $heading }}</span>
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
                        <a class="nav-link {{ $isActive ? 'active' : '' }}" href="{{ $item['url'] }}" @if($isActive) aria-current="page" @endif>
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
                onclick="event.preventDefault(); document.getElementById('student-sidebar-logout-form').submit();"
            >
                <span class="menu-title">Logout</span>
                <i class="mdi mdi-logout menu-icon"></i>
            </a>
            <form id="student-sidebar-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </li>
    </ul>
</nav>
