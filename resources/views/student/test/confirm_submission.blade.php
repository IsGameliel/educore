@extends('layouts.dash')

@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-home"></i>
                </span> Confirm Test Submission
            </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span> Confirm Submission
                        <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Registration Form -->
        <div class="card">
            <div class="card-body">
                <p>Review and submit your saved answers for the test: {{ $test->name }}.</p>

                 @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                <p id="confirmation-timer" class="alert alert-warning"></p>
                <form id="confirmation-form" action="{{ route('student.tests.submit', $test->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="submit" value="1">
                    <button type="submit" class="btn btn-primary">Submit Test</button>
                </form>
                <a href="{{ route('student.tests.index') }}" class="btn btn-secondary mt-3">Cancel</a>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    (() => {
        const deadline = @json($expiresAt);
        const serverNow = @json(now()->getTimestampMs());
        const loadedAt = performance.now();
        let submitting = false;
        function tick() {
            const left = Math.max(0, Math.ceil((deadline - serverNow - (performance.now() - loadedAt)) / 1000));
            document.getElementById('confirmation-timer').textContent = `Time remaining: ${Math.floor(left / 60)}:${String(left % 60).padStart(2, '0')}`;
            if (!left && !submitting) {
                submitting = true;
                document.getElementById('confirmation-form').requestSubmit();
            }
        }
        tick();
        setInterval(tick, 1000);
    })();
</script>
@endsection
