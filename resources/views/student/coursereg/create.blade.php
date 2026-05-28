@extends('layouts.dash')

@section('content')

    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-home"></i>
                    </span> Course Registration
                </h3>
                <nav aria-label="breadcrumb">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            <span></span>Register for Courses <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Registration Form -->
            <div class="card">
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('student.courses.register') }}" method="POST">
                        @csrf <!-- CSRF token for form submission -->

                        <div class="form-group">
                            <label>Academic Session:</label>
                            <input type="text" class="form-control" value="{{ $currentSession }}" readonly>
                            <input type="hidden" id="academic_session" value="{{ $currentSession }}">
                        </div>

                        <!-- Semester Field -->
                        <div class="form-group">
                            <label for="semester">Select Semester:</label>
                            <select name="semester" id="semester" class="form-control" required>
                                <option value="First" @selected(($defaultSemester ?? old('semester', 'First')) === 'First')>First</option>
                                <option value="Second" @selected(($defaultSemester ?? old('semester', 'First')) === 'Second')>Second</option>
                            </select>
                        </div>

                        <!-- Level Field -->
                        <div class="form-group">
                            <label for="level">Select Level:</label>
                            <select name="level" id="level" class="form-control" required>
                                <option value="">-- Select Level --</option>
                                <option value="100" @selected(old('level', (string) auth()->user()->level) === '100')>100 Level</option>
                                <option value="200" @selected(old('level', (string) auth()->user()->level) === '200')>200 Level</option>
                                <option value="300" @selected(old('level', (string) auth()->user()->level) === '300')>300 Level</option>
                                <option value="400" @selected(old('level', (string) auth()->user()->level) === '400')>400 Level</option>
                            </select>
                        </div>

                        <!-- Courses Field -->
                        <div class="form-group">
                            <label for="courses">Select Courses:</label>
                            <select name="course_ids[]" id="courses" class="form-control" multiple required>
                                @forelse($courses as $course)
                                    <option
                                        value="{{ $course->id }}"
                                        data-credit-unit="{{ $course->credit_unit }}"
                                        @selected(in_array($course->id, old('course_ids', [])))
                                    >
                                        {{ $course->code }} - {{ $course->title }} ({{ $course->credit_unit }} credits)
                                        @if($course->prerequisites->isNotEmpty())
                                            - Prerequisite:
                                            @foreach($course->prerequisites as $prerequisite)
                                                {{ $prerequisite->title }}{{ !$loop->last ? ', ' : '' }}
                                            @endforeach
                                        @endif
                                    </option>
                                @empty
                                    <option value="">No courses available for this semester</option>
                                @endforelse
                            </select>
                        </div>

                        <div class="mt-2">
                            <strong>Total Selected Credit Units: </strong>
                            <span id="totalCredits">0</span>
                        </div>

                        <div id="creditWarning" style="display:none; color:red; font-weight:bold; margin-top:10px;">
                            You have exceeded the maximum allowed credit units for this level!
                        </div>



                        <!-- Courses Field -->
                        {{-- <div class="form-group">
                            <label for="courses">Select Courses:</label>
                            <select name="course_ids[]" id="courses" class="form-control" multiple required>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">
                                        {{ $course->title }} ({{ $course->credit_unit }} credits)
                                        @if($course->prerequisites->isNotEmpty())
                                            - Prerequisite:
                                            @foreach($course->prerequisites as $prerequisite)
                                                {{ $prerequisite->title }}{{ !$loop->last ? ',' : '' }}
                                            @endforeach
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div> --}}



                        <!-- Submit Button -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-gradient-primary">Register for Selected Courses</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>
    </div>

   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    let creditLimits = {
        '100': 24,
        '200': 24,
        '300': 24,
        '400': 24
    };

    let courseCreditMapping = {}; // Store course_id => credit_unit

    function refreshCourseCreditsFromDom() {
        courseCreditMapping = {};

        $('#courses option').each(function () {
            const courseId = String($(this).val() || '');

            if (!courseId) {
                return;
            }

            courseCreditMapping[courseId] = parseInt($(this).data('credit-unit') || 0, 10);
        });
    }

    function updateCreditTotals() {
        let selected = $('#courses').val() || [];
        let totalCredits = 0;

        selected.forEach(courseId => {
            totalCredits += parseInt(courseCreditMapping[courseId] || 0);
        });

        $('#totalCredits').text(totalCredits);

        let level = $('#level').val();
        let maxLimit = creditLimits[level];

        if (maxLimit && totalCredits > maxLimit) {
            $('#creditWarning').show();
        } else {
            $('#creditWarning').hide();
        }
    }

    $('#level, #semester').on('change', function () {
        let level = $('#level').val();
        let semester = $('#semester').val();
        let session = $('#academic_session').val();

        if(level && semester) {
            $.ajax({
                url: "{{ route('student.courses.byLevel') }}",
                type: "GET",
                data: { level: level, semester: semester, session: session },
                success: function (courses) {
                    $('#courses').empty();

                    courseCreditMapping = {};

                    if (courses.length > 0) {
                        $.each(courses, function (index, course) {
                            courseCreditMapping[String(course.id)] = parseInt(course.credit_unit || 0, 10);
                            let prerequisites = '';
                            if (course.prerequisites.length > 0) {
                                prerequisites = ' - Prerequisite: ' + course.prerequisites.map(p => p.title).join(', ');
                            }

                            $('#courses').append(
                                `<option value="${course.id}" data-credit-unit="${course.credit_unit}">
                                    ${course.code} - ${course.title} (${course.credit_unit} credits)${prerequisites}
                                </option>`
                            );
                        });
                    } else {
                        $('#courses').append('<option value="">No courses available</option>');
                    }

                    refreshCourseCreditsFromDom();
                    updateCreditTotals();
                }
            });
        }
    });


    // Calculate credits when courses are selected
    $('#courses').on('change', function () {
        updateCreditTotals();
    });

    refreshCourseCreditsFromDom();
    updateCreditTotals();

    if (!{{ old('course_ids') ? 'true' : 'false' }}) {
        $('#level').trigger('change');
    }
</script>


@endsection
