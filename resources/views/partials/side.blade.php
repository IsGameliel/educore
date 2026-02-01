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
            <a class="nav-link" data-bs-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
                <span class="menu-title">Student Management</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-account-school menu-icon"></i>
            </a>
            <div class="collapse" id="ui-basic">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.students.index')}}">Add/Manage Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/ui-features/dropdowns.html">Enrollment & Admissions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/ui-features/typography.html">Attendance Records</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/ui-features/typography.html">Academic Performance</a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#staff" aria-expanded="false" aria-controls="auth">
                <span class="menu-title">Staff Management</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-account-key menu-icon"></i>
            </a>
            <div class="collapse" id="staff">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.staffs.index') }}"> Add/Manage Staff </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/login.html"> Assign Roles & Permissions </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/register.html"> Attendance & Leave Tracking </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#courses" aria-expanded="false" aria-controls="auth">
                <span class="menu-title">Courses & Classes</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-human-male-board menu-icon"></i>
            </a>
            <div class="collapse" id="courses">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.courses.index')}}"> Add/Manage Courses </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.faculties.index')}}"> Add/Manage Faculty </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.departments.index') }}"> Add/Manage Departments </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.class-schedules.index')}}"> Class Schedules & Timetables </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.course-materials.index')}}"> Lecture Materials </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/register.html"> Assign Instructors </a>
                    </li>
                </ul>
            </div>
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
                    <li class="nav-item">
                        <a class="nav-link" href="#"> Questions </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"> Publish Results </a>
                    </li>
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
            <a class="nav-link" data-bs-toggle="collapse" href="#finance" aria-expanded="false" aria-controls="auth">
                <span class="menu-title">Finance & Fees</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-wallet-bifold menu-icon"></i>
            </a>
            <div class="collapse" id="finance">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="#"> Fee Structure & Billing </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"> Payment Tracking </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"> Scholarships & Discounts </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/register.html"> Generate Invoices & Receipts </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#lib" aria-expanded="false" aria-controls="auth">
                <span class="menu-title">Library Management</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-library-outline menu-icon"></i>
            </a>
            <div class="collapse" id="lib">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/blank-page.html"> Add/Manage Books </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/login.html"> Issue/Return Records </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/register.html"> Fine Management </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#hostel" aria-expanded="false" aria-controls="auth">
                <span class="menu-title">Hostel & Transport</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-bed-outline menu-icon"></i>
            </a>
            <div class="collapse" id="hostel">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/blank-page.html"> Allocate Hostel Rooms </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/login.html"> Manage Transport Routes </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/register.html"> Assign Drivers/Vehicles </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#communication" aria-expanded="false" aria-controls="auth">
                <span class="menu-title">Communication</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-gesture-tap-button menu-icon"></i>
            </a>
            <div class="collapse" id="communication">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/blank-page.html"> Send Announcements </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/login.html"> Manage Messages & Notifications </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/register.html"> Email/SMS Integration </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#reports" aria-expanded="false" aria-controls="auth">
                <span class="menu-title">Reports & Analytics</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-chat-line menu-icon"></i>
            </a>
            <div class="collapse" id="reports">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/blank-page.html"> Generate Student Reports </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/login.html"> Financial Reports </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/register.html"> Attendance & Performance Analytics </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#user" aria-expanded="false" aria-controls="auth">
                <span class="menu-title">User Management</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-account-group menu-icon"></i>
            </a>
            <div class="collapse" id="user">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/blank-page.html"> Manage Roles & Permissions </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/login.html"> Create/Manage Admin Accounts </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/register.html"> Access Logs </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#settings" aria-expanded="false" aria-controls="auth">
                <span class="menu-title">Settings</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-cog menu-icon"></i>
            </a>
            <div class="collapse" id="settings">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/blank-page.html"> General Settings (School Info, Academic Calendar) </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/login.html"> Fee & Payment Settings </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/register.html"> Notification Preferences </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#support" aria-expanded="false" aria-controls="auth">
                <span class="menu-title">Support & Helpdesk</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-call-split menu-icon"></i>
            </a>
            <div class="collapse" id="support">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/blank-page.html"> View Tickets & Inquiries </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/login.html"> Knowledge Base / FAQs </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#audit" aria-expanded="false" aria-controls="auth">
                <span class="menu-title">Audit & Logs</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-post-outline menu-icon"></i>
            </a>
            <div class="collapse" id="audit">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/blank-page.html"> Activity Logs </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/samples/login.html"> User Access Logs </a>
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
