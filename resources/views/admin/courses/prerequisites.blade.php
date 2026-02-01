@extends('layouts.dash')

@section('content')

    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-home"></i>
                    </span> Courses
                </h3>
                <nav aria-label="breadcrumb">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            <span></span>Create Courses <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Registration Form -->
            <div class="card">
                <div class="card-body">
                    <h4>Manage Prerequisites for {{ $course->title }}</h4>

                        <form action="{{ route('admin.courses.assignPrerequisites', $course->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="prerequisites">Select Prerequisites:</label>
                                <select name="prerequisites[]" id="prerequisites" class="form-control" multiple>
                                    @foreach($allCourses as $c)
                                        <option value="{{ $c->id }}" {{ $prerequisites->contains($c->id) ? 'selected' : '' }}>
                                            {{ $c->title }} ({{ $c->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success">Update Prerequisites</button>
                        </form>

                        <h3 class="mt-4">Current Prerequisites</h3>
                        <ul>
                            @forelse($prerequisites as $prerequisite)
                                <li>{{ $prerequisite->title }} ({{ $prerequisite->code }})</li>
                            @empty
                                <li>No prerequisites assigned.</li>
                            @endforelse
                        </ul>
                </div>
            </div>
        </div>
        </div>
    </div>

@endsection
