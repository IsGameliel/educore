@extends('layouts.dash')

@section('content')

    <div class="main-panel">
        <div class="content-wrapper">
            @include('academic.registration-assistance')
            @if(! $registrationSettings->registration_open)
                <div class="alert alert-warning">Course registration is currently closed. Please wait for the administrator to reopen it.</div>
            @elseif($registrationSettings->require_fee_clearance)
                <div class="alert alert-info">Course registration is open. Required semester fees must be cleared before registration. <a href="{{ route('tuition.index') }}">View tuition balance and clearance</a>.</div>
            @else
                <div class="alert alert-info">Course registration is open. Fee clearance is currently not required.</div>
            @endif
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

                    <form method="GET" action="{{ route('student.courses.registration') }}" class="mb-3">
                        <label for="registration-session">Choose academic session</label>
                        <select name="session" id="registration-session" class="form-control" required>
                            @foreach($academicSessions as $academicSession)
                                <option value="{{ $academicSession }}" @selected($currentSession === $academicSession)>{{ $academicSession }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="semester" value="{{ $defaultSemester }}">
                        <button class="btn btn-outline-primary mt-2" type="submit">Load Session</button>
                    </form>
                    @if(!$sessionLevel)
                        <div class="alert alert-warning">Your level for {{ $currentSession }} has not been set. Ask an administrator to save your session level in Course Registrations before registering.</div>
                    @endif

                    @if($registrationSettings->require_late_registration_fee)
                        <div class="alert alert-info">
                            <strong>Late registration: ₦5,000 per session and semester.</strong>
                            <span id="late-fee-status">{{ $lateFeePaid[$defaultSemester] ? 'Your payment has been verified for this semester.' : 'Payment must be verified before you can register.' }}</span>
                            @if($registrationSettings->registration_open && $sessionLevel)
                                <form id="late-fee-payment" method="POST" action="{{ route('student.courses.late-payment') }}" class="mt-2" @if($lateFeePaid[$defaultSemester]) hidden @endif>
                                    @csrf
                                    <input type="hidden" name="session" value="{{ $currentSession }}">
                                    <input type="hidden" name="semester" id="late-fee-semester" value="{{ $defaultSemester }}">
                                    <button type="submit" class="btn btn-outline-primary">Pay ₦5,000 with Paystack</button>
                                </form>
                            @endif
                        </div>
                    @endif
                    <form action="{{ route('student.courses.register') }}" method="POST">
                        @csrf <!-- CSRF token for form submission -->

                        <div class="form-group">
                            <label>Academic Session:</label>
                            <input type="text" class="form-control" value="{{ $currentSession }}" readonly>
                            <input type="hidden" name="session" id="academic_session" value="{{ $currentSession }}">
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
                            <label for="level">Level for {{ $currentSession }}:</label>
                            <input id="level" type="hidden" name="level" value="{{ $sessionLevel }}">
                            <div class="form-control">{{ $sessionLevel ? $sessionLevel.' Level' : 'Not set' }}</div>
                        </div>

                        <!-- Courses Field -->
                        <div class="form-group">
                            <label for="courses">Select Courses:</label>
                            <select name="course_ids[]" id="courses" class="form-control" multiple>
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

                        <fieldset class="form-group">
                            <legend class="h5">Outstanding / Carryover Courses</legend>
                            <p>Outstanding failed courses from earlier sessions are automatically made available in their original semester. Select them to register alongside your current courses. Previous marks remain on record. Carryover credits count toward your semester limit.</p>
                            @forelse($carryoverCourses as $carryover)
                                <label class="d-block carryover-option" data-semester="{{ $carryover->semester }}">
                                    <input type="checkbox" name="carryover_ids[]" value="{{ $carryover->id }}"
                                           data-credit-unit="{{ $carryover->credit_unit }}" @checked(in_array($carryover->id, old('carryover_ids', [])))>
                                    {{ $carryover->code }} — {{ $carryover->title }} ({{ $carryover->credit_unit }} credits)
                                    — Failed in {{ $carryover->failedResult->session }}, {{ $carryover->semester }} semester
                                </label>
                            @empty
                                <p class="text-muted">No outstanding published failures from earlier sessions are available. If a failed course is missing, ask the exam officer to check its published result.</p>
                            @endforelse
                        </fieldset>

                        <div class="mt-2">
                            <p>Maximum credit load this semester: <strong id="creditLimit">{{ $creditLimits[$defaultSemester] }}</strong></p>
                            <strong>Total Selected Credit Units: </strong>
                            <span id="totalCredits">0</span>
                        </div>

                        <div id="creditWarning" style="display:none; color:red; font-weight:bold; margin-top:10px;">
                            You have exceeded the maximum allowed credit units for this semester!
                        </div>



                        <!-- Courses Field -->
                        {{-- <div class="form-group">
                            <label for="courses">Select Courses:</label>
                            <select name="course_ids[]" id="courses" class="form-control" multiple>
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
                            <button type="submit" class="btn btn-gradient-primary" @disabled(! $registrationSettings->registration_open || !$sessionLevel)>Register for Selected Courses</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>
    </div>

   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    let creditLimits = @json($creditLimits);
    const lateFeePaid = @json($lateFeePaid);
    function updateLateFee() {
        const semester = $('#semester').val();
        const status = document.getElementById('late-fee-status');
        const paymentForm = document.getElementById('late-fee-payment');
        if (status) status.textContent = lateFeePaid[semester] ? 'Your payment has been verified for this semester.' : 'Payment must be verified before you can register.';
        if (paymentForm) {
            document.getElementById('late-fee-semester').value = semester;
            paymentForm.hidden = !!lateFeePaid[semester];
        }
    }
    $('#semester').on('change', updateLateFee);
    updateLateFee();

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

        $('.carryover-option input:checked:not(:disabled)').each(function () {
            if (!selected.includes(String(this.value))) totalCredits += Number($(this).data('credit-unit'));
        });
        $('#totalCredits').text(totalCredits);

        let level = $('#level').val();
        let maxLimit = creditLimits[$('#semester').val()];
        $('#creditLimit').text(maxLimit);

        if (maxLimit && totalCredits > maxLimit) {
            $('#creditWarning').show();
        } else {
            $('#creditWarning').hide();
        }
    }

    function filterCarryovers() {
        $('.carryover-option').each(function () {
            const visible = $(this).data('semester') === $('#semester').val();
            $(this).toggle(visible).find('input').prop('disabled', !visible);
            if (!visible) $(this).find('input').prop('checked', false);
        });
        updateCreditTotals();
    }
    $('.carryover-option input').on('change', updateCreditTotals);
    filterCarryovers();
    $('#level, #semester').on('change', function () {
        filterCarryovers();
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
