@php
    $examOfficerSections = [
        'Results Management' => [
            ['Review & Publish', 'academic.index', ['academic.index', 'academic.show', 'academic.update', 'academic.transition', 'academic.batch', 'academic.resit', 'academic.correction*'], 'mdi-clipboard-check'],
            ['Enter Results', 'academic.entry', ['academic.entry', 'academic.results.store', 'academic.results.students'], 'mdi-pencil'],
            ['Upload Results', 'academic.upload', ['academic.upload', 'academic.results.storeUpload', 'academic.results.template.*'], 'mdi-upload'],
        ],
        'Academic Records' => [
            ['Result Appeals', 'academic.appeals', ['academic.appeals*'], 'mdi-comment-alert-outline'],
            ['Transcripts', 'academic.transcripts', ['academic.transcripts*'], 'mdi-file-document'],
            ['Academic Reports', 'academic.reports', ['academic.reports*'], 'mdi-chart-bar'],
        ],
        'Academic Settings' => [
            ['Grading Policies', 'academic.policies', ['academic.policies*'], 'mdi-settings'],
        ],
    ];
@endphp

<style>
    #sidebar .nav .staff-section-heading {
        margin: 1.5rem 2.25rem 0.75rem;
        padding: 0;
        color: #7b8190;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        list-style: none;
    }
    .sidebar-icon-only #sidebar .nav .staff-section-heading {
        display: none;
    }
</style>

<nav class="sidebar sidebar-offcanvas" id="sidebar" aria-label="Staff navigation" data-route-navigation="true">
    <ul class="nav">
        <li class="nav-item nav-profile">
            <a href="{{ route('profile.show') }}" class="nav-link">
                <div class="nav-profile-image"><img src="{{ auth()->user()->profile_photo_url }}" alt="Profile"></div>
                <div class="nav-profile-text d-flex flex-column">
                    <span class="font-weight-bold mb-2">{{ auth()->user()->name }}</span>
                    <span class="text-secondary text-small">{{ auth()->user()->role_name }}</span>
                </div>
            </a>
        </li>
        <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                <span class="menu-title">Dashboard</span><i class="mdi mdi-home menu-icon"></i>
            </a>
        </li>

        @if(auth()->user()->dashboardRole() === 'exam_officer')
            @foreach($examOfficerSections as $heading => $links)
                <li class="staff-section-heading"><span>{{ $heading }}</span></li>
                @foreach($links as [$label, $route, $patterns, $icon])
                    @php($active = request()->routeIs(...$patterns))
                    <li class="nav-item {{ $active ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route($route) }}" @if($active) aria-current="page" @endif>
                            <span class="menu-title">{{ $label }}</span><i class="mdi {{ $icon }} menu-icon"></i>
                        </a>
                    </li>
                @endforeach
            @endforeach
            <li class="staff-section-heading"><span>Account</span></li>
        @endif

        <li class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('profile.show') }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif>
                <span class="menu-title">My Profile</span><i class="mdi mdi-account-circle menu-icon"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('staff-logout-form').submit();">
                <span class="menu-title">Logout</span><i class="mdi mdi-logout menu-icon"></i>
            </a>
            <form id="staff-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
        </li>
    </ul>
</nav>
