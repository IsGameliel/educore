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
                        <span></span> Register for Courses
                        <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Registration Form -->
        <div class="card">
            <div class="card-body">
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <ul class="list-group">
                    @foreach ($tests as $test)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ $test->name }} ({{ $test->subject }})</span>
                            @if ($test->status)
                                <a href="{{ route('student.tests.start', $test->id) }}" class="btn btn-primary btn-sm">Start Test</a>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
</div>

@endsection
