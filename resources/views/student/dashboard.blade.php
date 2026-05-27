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
                            <img src="dash/assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                            <h4 class="font-weight-normal mb-3">Lecture Material <i class="mdi mdi-chart-line mdi-24px float-end"></i>
                            </h4>
                            <h2 class="mb-5">{{ $courseMaterialsCount }}</h2>
                            <h6 class="card-text">Lecturer Materials available</h6>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 stretch-card grid-margin">
                    <div class="card bg-gradient-info card-img-holder text-white">
                        <div class="card-body">
                            <img src="dash/assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                            <h4 class="font-weight-normal mb-3">Courses <i class="mdi mdi-bookmark-outline mdi-24px float-end"></i>
                            </h4>
                            <h2 class="mb-5">{{ $courseCount }}</h2>
                            <h6 class="card-text">Available courses</h6>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 stretch-card grid-margin">
                    <div class="card bg-gradient-success card-img-holder text-white">
                        <div class="card-body">
                            <img src="dash/assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                            <h4 class="font-weight-normal mb-3">Events <i class="mdi mdi-diamond mdi-24px float-end"></i>
                            </h4>
                            <h2 class="mb-5">10</h2>
                            <h6 class="card-text">Coming Soon</h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 grid-margin">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Time table</h4>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th> Lecturer </th>
                                        <th> Subject </th>
                                        <th> Lecture Hall </th>
                                        <th> Time/Date </th>
                                        <th> Day </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($schedules as $schedule)
                                        <tr>
                                            <td>
                                                <img src="dash/assets/images/faces/face1.jpg" class="me-2" alt="image"> {{ $schedule->lecturer ? $schedule->lecturer->name : 'N/A' }}
                                            </td>
                                            <td>{{ $schedule->subject }}</td>
                                            <td>
                                                <label class="badge badge-gradient-success">{{ $schedule->room }}</label>
                                            </td>
                                            <td>{{ $schedule->start_time }} - {{ $schedule->end_time }}</td>
                                            <td>{{ $schedule->day }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
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
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <h4 class="card-title mb-1">Recent Updates</h4>
                                    <p class="text-muted mb-0">Course and result updates that apply to your department or record.</p>
                                </div>
                                <span class="badge badge-gradient-primary">Live</span>
                            </div>

                            @php($updates = $recentUpdates ?? collect())

                            @forelse($updates as $update)
                                <div class="d-flex align-items-start py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                    <div class="me-3">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-gradient-{{ $update['status_color'] }} text-white" style="width: 42px; height: 42px;">
                                            <i class="mdi {{ $update['icon'] ?? (str_contains(strtolower($update['title']), 'result') ? 'mdi-file-document-check-outline' : 'mdi-book-open-page-variant') }}"></i>
                                        </span>
                                    </div>

                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <h6 class="mb-0 text-dark">{{ $update['title'] }}</h6>
                                            <span class="badge badge-gradient-{{ $update['status_color'] }}">{{ $update['status'] }}</span>
                                        </div>

                                        <p class="mb-2 text-muted">{{ $update['details'] }}</p>

                                        <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                                            <span><i class="mdi mdi-account-outline me-1"></i>{{ $update['actor'] }}</span>
                                            @if($update['course_code'])
                                                <span><i class="mdi mdi-bookmark-outline me-1"></i>{{ $update['course_code'] }}</span>
                                            @endif
                                            @if($update['semester'])
                                                <span><i class="mdi mdi-calendar-range me-1"></i>{{ $update['semester'] }} Semester</span>
                                            @endif
                                            <span><i class="mdi mdi-clock-outline me-1"></i>{{ optional($update['occurred_at'])->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <i class="mdi mdi-bell-outline text-muted" style="font-size: 42px;"></i>
                                    <h5 class="mt-3 mb-1 text-dark">No recent updates yet</h5>
                                    <p class="text-muted mb-0">Course changes and published results for you will appear here.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            @include('dashboard.widgets')
        </div>
        <!-- content-wrapper ends -->
@endsection
