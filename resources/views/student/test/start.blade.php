@extends('layouts.dash')

@php
    $isLastQuestion = ($questionIndex + 1) === $questionCount;
@endphp

@push('styles')
<style>
    body.test-lockdown {
        user-select: none;
    }
</style>
@endpush

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
                <h5>Question {{ $questionIndex + 1 }} of {{ $questionCount }}</h5>
                <p><strong>{{ $question->question_text }}</strong></p>
                <div id="timer" class="alert alert-warning text-center mt-3">Loading timer...</div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: {{ ($questionIndex + 1) / $questionCount * 100 }}%;">
                        Question {{ $questionIndex + 1 }} of {{ $questionCount }}
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
                                @checked((string) $selectedAnswer === (string) $key && $selectedAnswer !== null) required>
                            <label class="form-check-label" for="option-{{ $question->id }}-{{ $key }}">
                                {{ $option }}
                            </label>
                        </div>
                    @endforeach

                    <div class="mt-3">
                        <button type="submit" id="submit-btn" class="btn btn-primary">
                            {{ $questionIndex + 1 < $questionCount ? 'Next' : 'Submit' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const deadline = @json($expiresAt);
            const serverNow = @json(now()->getTimestampMs());
            const loadedAt = performance.now();
            const form = document.getElementById('question-form');
            const button = document.getElementById('submit-btn');
            const timer = document.getElementById('timer');
            const submitUrl = @json(route('student.tests.submit', $test->id));
            let busy = false;
            let retryAt = 0;
            async function send(url, data) {
                if (busy) return;
                busy = true;
                button.disabled = true;
                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())},
                        body: data
                    });
                    if (!(response.headers.get('content-type') || '').includes('application/json')) {
                        throw new Error('Your session expired or the server is unavailable. Refresh this page to resume your saved attempt.');
                    }
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Unable to save. Please try again.');
                    window.location.assign(result.nextUrl);
                } catch (error) {
                    timer.textContent = error.message;
                    retryAt = performance.now() + 5000;
                    busy = false;
                    button.disabled = false;
                }
            }
            function remaining() {
                return deadline - serverNow - (performance.now() - loadedAt);
            }
            function tick() {
                const left = Math.max(0, Math.ceil(remaining() / 1000));
                if (performance.now() >= retryAt) {
                    timer.textContent = left ? `Time remaining: ${Math.floor(left / 60)}:${String(left % 60).padStart(2, '0')}` : 'Time expired. Submitting saved answers…';
                    if (!left) send(submitUrl, new FormData());
                }
            }
            form.addEventListener('submit', event => {
                event.preventDefault();
                send(remaining() <= 0 ? submitUrl : form.action, remaining() <= 0 ? new FormData() : new FormData(form));
            });
            tick();
            setInterval(tick, 1000);
        })();
    </script>
</div>
@endsection
