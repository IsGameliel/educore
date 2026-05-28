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
                <p>You have answered all questions for the test: {{ $test->name }}.</p>

                 @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                <form action="{{ route('student.tests.submit', $test->id) }}" method="POST">
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

@endsection
