@extends('layouts.dash')

@php
    $isLastQuestion = ($questionIndex + 1) === $test->questions->count();
@endphp

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-home"></i>
                </span> Test Portal
            </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span> Take Test
                        <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Test Question Form -->
        <div class="card">
            <div class="card-body">
                <h5>Question {{ $questionIndex + 1 }} of {{ $test->questions->count() }}</h5>
                <p><strong>{{ $question->question_text }}</strong></p>
                <div id="timer" class="alert alert-warning text-center mt-3">Loading timer...</div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: {{ ($questionIndex + 1) / $test->questions->count() * 100 }}%;">
                        Question {{ $questionIndex + 1 }} of {{ $test->questions->count() }}
                    </div>
                </div>

                <!-- Form for submitting answers -->
                <form
                    action="{{ route('student.tests.storeAnswer', [$test->id, $questionIndex]) }}"
                    method="POST"
                    id="question-form">
                    @csrf

                    @foreach ($question->options as $key => $option)
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="answers[{{ $question->id }}]"
                                value="{{ $key }}"
                                id="option-{{ $question->id }}-{{ $key }}"
                                required>
                            <label class="form-check-label" for="option-{{ $question->id }}-{{ $key }}">
                                {{ $option }}
                            </label>
                        </div>
                    @endforeach

                    <div class="mt-3">
                        <button type="button" id="submit-btn" class="btn btn-primary" disabled>
                            {{ $questionIndex + 1 < $test->questions->count() ? 'Next' : 'Submit' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

        <script>
            (function () {

                const testId = {{ $test->id ?? '0' }};
                const isLastQuestion = {{ $isLastQuestion ? 'true' : 'false' }};
                const questionFormAction = "{{ route('student.tests.storeAnswer', [$test->id, $questionIndex]) }}";
                const testSubmitUrl = "{{ route('student.tests.submit', [$test->id]) }}";
                if (!Number.isInteger(testId) || testId <= 0) {
                    console.error('Invalid testId:', testId);
                    document.getElementById('timer').innerText = 'Error: Invalid test ID';
                    document.getElementById('submit-btn').disabled = true;
                    return;
                }

                let startTime = sessionStorage.getItem(`startTime_${testId}`);
                if (!startTime) {
                    startTime = new Date().getTime();
                    sessionStorage.setItem(`startTime_${testId}`, startTime);
                    console.log('New startTime set:', startTime);
                } else {
                    startTime = parseInt(startTime, 10);
                    if (isNaN(startTime) || startTime <= 0) {
                        console.error('Invalid startTime:', startTime);
                        startTime = new Date().getTime();
                        sessionStorage.setItem(`startTime_${testId}`, startTime);
                        console.log('Reset startTime:', startTime);
                    } else {
                        console.log('Retrieved startTime:', startTime);
                    }
                }

                const durationMinutes = {{ $test->duration ?? 0 }};
                if (!Number.isInteger(durationMinutes) || durationMinutes <= 0) {
                    console.error('Invalid duration:', durationMinutes);
                    document.getElementById('timer').innerText = 'Error: Invalid test duration';
                    document.getElementById('submit-btn').disabled = true;
                    return;
                }

                // Correct endTime calculation
                const endTime = startTime + durationMinutes * 60 * 1000;
                console.log('endTime:', endTime);

                function updateTimer() {
                    try {
                        const now = new Date().getTime();
                        const timeLeft = endTime - now;

                        if (timeLeft <= 0) {
                            document.getElementById('timer').innerText = 'Time’s up! Submitting...';
                            document.getElementById('submit-btn').disabled = true;
                            submitTest();
                            return;
                        }

                        const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                        document.getElementById('timer').innerText = `Time Remaining: ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    } catch (error) {
                        console.error('updateTimer error:', error);
                        document.getElementById('timer').innerText = 'Error: Timer update failed';
                    }
                }

                function submitTest(retryCount = 0) {
                        const maxRetries = 2;
                        try {
                            const form = document.getElementById('question-form');
                            const formData = new FormData(form);
                            formData.append('_token', "{{ csrf_token() }}");
                            formData.append('submit', '1');

                            let url = isLastQuestion ? testSubmitUrl : questionFormAction;

                            fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                                },
                                body: formData
                            })
                            .then(response => {
                                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                                return response.json();
                            })
                            .then(data => {
                                sessionStorage.removeItem(`startTime_${testId}`);
                                if (isLastQuestion) {
                                    window.location.href = testSubmitUrl;
                                } else if (data.nextUrl) {
                                    window.location.href = data.nextUrl;
                                } else {
                                    window.location.reload();
                                }
                            })
                            .catch(error => {
                                if (retryCount < maxRetries) {
                                    setTimeout(() => submitTest(retryCount + 1), 1000);
                                } else {
                                    document.getElementById('timer').innerText = 'Error: Failed to submit test';
                                    alert('Failed to submit test. Please try again or contact support.');
                                }
                            });
                        } catch (error) {
                            document.getElementById('timer').innerText = 'Error: Submission failed';
                            alert('An error occurred while submitting the test.');
                        }
                    }

                // Start timer immediately
                updateTimer();
                const timerInterval = setInterval(updateTimer, 1000);

                // Clear interval on page unload
                window.addEventListener('unload', () => clearInterval(timerInterval));

                // Enable button when an answer is selected
                const radioButtons = document.querySelectorAll('input[name="answers[{{ $question->id }}]"]');
                radioButtons.forEach(radio => {
                    radio.addEventListener('change', () => {
                        document.getElementById('submit-btn').disabled = false;
                    });
                });

                // Handle form submission
                document.getElementById('submit-btn').addEventListener('click', () => {
                    const form = document.getElementById('question-form');
                    const formData = new FormData(form);
                    const btn = document.getElementById('submit-btn');
                    btn.disabled = true;

                    // Client-side validation
                    if (!form.querySelector('input[name="answers[{{ $question->id }}]"]:checked')) {
                        alert('Please select an answer.');
                        btn.disabled = false;
                        return;
                    }

                    // Check timer before submitting
                    const now = new Date().getTime();
                    if (now > endTime) {
                        alert('Test time has expired. Submitting test now.');
                        submitTest();
                        return;
                    }

                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Answer submission response:', data);
                        if (data.success) {
                            window.location.href = data.nextUrl;
                        } else {
                            alert(data.message || 'An error occurred. Please try again.');
                            btn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Answer submission error:', error);
                        alert('An error occurred while submitting your answer.');
                        btn.disabled = false;
                    });
                });
            })();
            </script>
    </div>
</div>
@endsection
