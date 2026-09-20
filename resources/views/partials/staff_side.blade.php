<nav class="sidebar sidebar-offcanvas" id="sidebar" aria-label="Staff navigation">
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
        <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}"><span class="menu-title">Dashboard</span><i class="mdi mdi-home menu-icon"></i></a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('profile.show') }}"><span class="menu-title">Profile</span><i class="mdi mdi-account-circle menu-icon"></i></a></li>
    </ul>
</nav>
