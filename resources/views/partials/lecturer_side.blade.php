<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        <li class="nav-item nav-profile">
            <a href="#" class="nav-link">
                <div class="nav-profile-image">
                    <img src="{{ Auth::user()->profile_photo_url }}" alt="profile" />
                    <span class="login-status online"></span>
                    <!--change to offline or busy as needed-->
                </div>
                <div class="nav-profile-text d-flex flex-column">
                    <span class="font-weight-bold mb-2">{{ Auth::user()->name }}</span>
                    <span class="text-secondary text-small">{{ Auth::user()->usertype }}</span>
                </div>
                <i class="mdi mdi-bookmark-check text-success nav-profile-badge"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ url('home') }}">
                <span class="menu-title">Dashboard</span>
                <i class="mdi mdi-home menu-icon"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#exams" aria-expanded="false" aria-controls="auth">
                <span class="menu-title">Test Maker</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-laptop menu-icon"></i>
            </a>
            <div class="collapse" id="exams">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.tests.index') }}">Tests</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="#"> Questions </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"> Publish Results </a>
                    </li> -->
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#assessments" aria-expanded="false" aria-controls="auth">
                <span class="menu-title">Exams & Assessments</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-lead-pencil menu-icon"></i>
            </a>
            <div class="collapse" id="assessments">
                <ul class="nav flex-column sub-menu">
                    
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.results.index') }}"> Manage Grades & Results </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.results.create') }}"> Publish Results </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault();
                          document.getElementById('logout-form').submit();">
                <span class="menu-title">Logout</span>
                <i class="mdi mdi-logout menu-icon"></i>
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </li>
    </ul>
</nav>
