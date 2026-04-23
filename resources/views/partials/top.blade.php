<nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
        <a class="navbar-brand brand-logo" href="{{ url('/') }}"><img src="{{ asset('asset/images/educore.png')}}" style="width: 40%; height: 90px; padding-top: 12px; margin-left: 40px;" alt="logo" /></a>
        {{--            <a class="navbar-brand brand-logo-mini" href="index.html"><img src="assets/images/logo-mini.svg" alt="logo" /></a>--}}
    </div>
    <div class="navbar-menu-wrapper d-flex align-items-stretch">
        <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
            <span class="mdi mdi-menu"></span>
        </button>
        <div class="search-field d-none d-md-block">
            <form class="d-flex align-items-center h-100" action="#">
                <div class="input-group">
                    <div class="input-group-prepend bg-transparent">
                        <i class="input-group-text border-0 mdi mdi-magnify"></i>
                    </div>
                    <input type="text" class="form-control bg-transparent border-0" placeholder="Search projects">
                </div>
            </form>
        </div>
        <ul class="navbar-nav navbar-nav-right">
            <li class="nav-item nav-profile dropdown">
                <a class="nav-link dropdown-toggle" id="profileDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="nav-profile-img">
                        <img src="{{ Auth::user()->profile_photo_url }}" alt="image">
                        <span class="availability-status online"></span>
                    </div>
                    <div class="nav-profile-text">
                        <p class="mb-1 text-black">{{ Auth::user()->name }}</p>
                    </div>
                </a>
                <div class="dropdown-menu navbar-dropdown" aria-labelledby="profileDropdown">
                    <a class="dropdown-item" href="{{ url('user/profile') }}">
                        <i class="mdi mdi-cached me-2 text-success"></i> Profile </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('logout') }}"
                       onclick="event.preventDefault();
                          document.getElementById('logout-form').submit();">
                        <i class="mdi mdi-logout me-2 text-primary"></i>
                        {{ __('Logout') }}
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </li>
            <li class="nav-item d-none d-lg-block full-screen-link">
                <a class="nav-link">
                    <i class="mdi mdi-fullscreen" id="fullscreen-button"></i>
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link count-indicator dropdown-toggle" id="messageDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="mdi mdi-email-outline"></i>
                    <span class="count-symbol bg-warning"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end navbar-dropdown preview-list" aria-labelledby="messageDropdown">
                    <h6 class="p-3 mb-0">Messages</h6>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item preview-item">
                        <div class="preview-thumbnail">
                            <img src="assets/images/faces/face4.jpg" alt="image" class="profile-pic">
                        </div>
                        <div class="preview-item-content d-flex align-items-start flex-column justify-content-center">
                            <h6 class="preview-subject ellipsis mb-1 font-weight-normal">Mark send you a message</h6>
                            <p class="text-gray mb-0"> 1 Minutes ago </p>
                        </div>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item preview-item">
                        <div class="preview-thumbnail">
                            <img src="assets/images/faces/face2.jpg" alt="image" class="profile-pic">
                        </div>
                        <div class="preview-item-content d-flex align-items-start flex-column justify-content-center">
                            <h6 class="preview-subject ellipsis mb-1 font-weight-normal">Cregh send you a message</h6>
                            <p class="text-gray mb-0"> 15 Minutes ago </p>
                        </div>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item preview-item">
                        <div class="preview-thumbnail">
                            <img src="assets/images/faces/face3.jpg" alt="image" class="profile-pic">
                        </div>
                        <div class="preview-item-content d-flex align-items-start flex-column justify-content-center">
                            <h6 class="preview-subject ellipsis mb-1 font-weight-normal">Profile picture updated</h6>
                            <p class="text-gray mb-0"> 18 Minutes ago </p>
                        </div>
                    </a>
                    <div class="dropdown-divider"></div>
                    <h6 class="p-3 mb-0 text-center">4 new messages</h6>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown" href="#" data-bs-toggle="dropdown">
                    <i class="mdi mdi-bell-outline"></i>
                    @if(($studentUpdateNotificationCount ?? 0) > 0)
                        <span class="position-absolute translate-middle badge rounded-pill bg-danger notification-count-badge" data-notification-count style="top: 18px; right: -2px; font-size: 0.62rem; min-width: 1.15rem;">
                            {{ $studentUpdateNotificationCount > 9 ? '9+' : $studentUpdateNotificationCount }}
                        </span>
                        <span class="count-symbol bg-secondary notification-count-default d-none" data-notification-default-indicator></span>
                    @else
                        <span class="count-symbol bg-secondary notification-count-default" data-notification-default-indicator></span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end navbar-dropdown preview-list" aria-labelledby="notificationDropdown">
                    <h6 class="p-3 mb-0">Notifications</h6>
                    <div class="dropdown-divider"></div>

                    @forelse(($studentUpdateNotifications ?? collect()) as $notification)
                        <button
                            type="button"
                            class="dropdown-item preview-item student-notification-item"
                            data-bs-toggle="modal"
                            data-bs-target="#studentNotificationModal"
                            data-notification-id="{{ $notification['id'] }}"
                            data-notification-title="{{ $notification['title'] }}"
                            data-notification-status="{{ $notification['status'] }}"
                            data-notification-status-color="{{ $notification['status_color'] }}"
                            data-notification-details="{{ $notification['details'] }}"
                            data-notification-actor="{{ $notification['actor'] }}"
                            data-notification-course="{{ $notification['course_code'] ?? '' }}"
                            data-notification-semester="{{ $notification['semester'] ?? '' }}"
                            data-notification-time="{{ optional($notification['occurred_at'])->diffForHumans() }}"
                            data-notification-icon="{{ $notification['icon'] ?? 'mdi-bell-outline' }}"
                        >
                            <div class="preview-thumbnail">
                                <div class="preview-icon bg-{{ $notification['status_color'] }}">
                                    <i class="mdi {{ $notification['icon'] ?? 'mdi-bell-outline' }}"></i>
                                </div>
                            </div>
                            <div class="preview-item-content d-flex align-items-start flex-column justify-content-center">
                                <h6 class="preview-subject font-weight-normal mb-1">
                                    {{ $notification['title'] }}
                                    <span class="badge badge-gradient-{{ $notification['status_color'] }} ms-1">{{ $notification['status'] }}</span>
                                </h6>
                                <p class="text-gray ellipsis mb-0">{{ $notification['details'] }}</p>
                                <p class="text-gray mb-0 small">
                                    {{ optional($notification['occurred_at'])->diffForHumans() }}
                                    @if($notification['course_code'])
                                        · {{ $notification['course_code'] }}
                                    @endif
                                </p>
                            </div>
                        </button>
                        <div class="dropdown-divider"></div>
                    @empty
                        <div class="px-3 py-4 text-center">
                            <i class="mdi mdi-bell-outline text-muted" style="font-size: 32px;"></i>
                            <p class="text-muted mb-0 mt-2">No new updates yet.</p>
                        </div>
                        <div class="dropdown-divider"></div>
                    @endforelse

                    <a href="{{ route('dashboard') }}" class="dropdown-item text-center">
                        View dashboard updates
                    </a>
                </div>
            </li>
            <li class="nav-item nav-logout d-none d-lg-block">
                <a class="nav-link" href="#">
                    <i class="mdi mdi-power"></i>
                </a>
            </li>
            <li class="nav-item nav-settings d-none d-lg-block">
                <a class="nav-link" href="#">
                    <i class="mdi mdi-format-line-spacing"></i>
                </a>
            </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
            <span class="mdi mdi-menu"></span>
        </button>
    </div>
</nav>

@if(Auth::user()->usertype === 'student')
    <div class="modal fade" id="studentNotificationModal" tabindex="-1" aria-labelledby="studentNotificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-2">
                    <div class="d-flex align-items-center gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white student-notification-modal-icon bg-gradient-primary" style="width: 46px; height: 46px;">
                            <i class="mdi mdi-bell-outline"></i>
                        </span>
                        <div>
                            <h5 class="modal-title mb-1" id="studentNotificationModalLabel">Notification</h5>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge badge-gradient-primary" data-modal-status>Updated</span>
                                <small class="text-muted" data-modal-time></small>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="text-muted mb-3" data-modal-details></p>

                    <div class="border rounded p-3 bg-light">
                        <div class="row g-3 small">
                            <div class="col-12">
                                <span class="text-muted d-block">Updated By</span>
                                <strong data-modal-actor>System</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Course</span>
                                <strong data-modal-course>General update</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Semester</span>
                                <strong data-modal-semester>Not specified</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const userId = @json(Auth::id());
            const storageKey = `student_notification_reads_${userId}`;
            const notificationItems = Array.from(document.querySelectorAll('.student-notification-item'));
            const countBadge = document.querySelector('[data-notification-count]');
            const defaultIndicator = document.querySelector('[data-notification-default-indicator]');
            const notificationModalElement = document.getElementById('studentNotificationModal');

            if (!notificationItems.length || !notificationModalElement) {
                return;
            }

            function safeReadMap() {
                try {
                    const raw = window.localStorage.getItem(storageKey);
                    return raw ? JSON.parse(raw) : {};
                } catch (error) {
                    return {};
                }
            }

            function writeReadMap(value) {
                window.localStorage.setItem(storageKey, JSON.stringify(value));
            }

            function getUnreadCount() {
                const readMap = safeReadMap();

                return notificationItems.filter(function (item) {
                    return !readMap[item.dataset.notificationId];
                }).length;
            }

            function renderNotificationCount() {
                const unreadCount = getUnreadCount();

                if (countBadge) {
                    if (unreadCount > 0) {
                        countBadge.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
                        countBadge.classList.remove('d-none');
                    } else {
                        countBadge.classList.add('d-none');
                    }
                }

                if (defaultIndicator) {
                    defaultIndicator.classList.toggle('d-none', unreadCount > 0);
                }
            }

            function markAsRead(notificationId) {
                if (!notificationId) {
                    return;
                }

                const readMap = safeReadMap();
                readMap[notificationId] = true;
                writeReadMap(readMap);
                renderNotificationCount();
            }

            const modalFields = {
                title: notificationModalElement.querySelector('#studentNotificationModalLabel'),
                status: notificationModalElement.querySelector('[data-modal-status]'),
                time: notificationModalElement.querySelector('[data-modal-time]'),
                details: notificationModalElement.querySelector('[data-modal-details]'),
                actor: notificationModalElement.querySelector('[data-modal-actor]'),
                course: notificationModalElement.querySelector('[data-modal-course]'),
                semester: notificationModalElement.querySelector('[data-modal-semester]'),
                iconWrap: notificationModalElement.querySelector('.student-notification-modal-icon'),
                icon: notificationModalElement.querySelector('.student-notification-modal-icon i')
            };

            notificationItems.forEach(function (item) {
                item.addEventListener('click', function () {
                    const statusColor = item.dataset.notificationStatusColor || 'primary';

                    modalFields.title.textContent = item.dataset.notificationTitle || 'Notification';
                    modalFields.status.textContent = item.dataset.notificationStatus || 'Updated';
                    modalFields.status.className = `badge badge-gradient-${statusColor}`;
                    modalFields.time.textContent = item.dataset.notificationTime || '';
                    modalFields.details.textContent = item.dataset.notificationDetails || 'No details available.';
                    modalFields.actor.textContent = item.dataset.notificationActor || 'System';
                    modalFields.course.textContent = item.dataset.notificationCourse || 'General update';
                    modalFields.semester.textContent = item.dataset.notificationSemester ? `${item.dataset.notificationSemester} Semester` : 'Not specified';
                    modalFields.iconWrap.className = `d-inline-flex align-items-center justify-content-center rounded-circle text-white student-notification-modal-icon bg-gradient-${statusColor}`;
                    modalFields.iconWrap.style.width = '46px';
                    modalFields.iconWrap.style.height = '46px';
                    modalFields.icon.className = `mdi ${item.dataset.notificationIcon || 'mdi-bell-outline'}`;

                    markAsRead(item.dataset.notificationId);
                });
            });

            renderNotificationCount();
        });
    </script>
@endif
