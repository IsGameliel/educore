@extends('layouts.dash')

@section('content')

    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-home"></i>
                    </span> Faculties
                </h3>
                <nav aria-label="breadcrumb">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            <span></span>View Faculties <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Registration Form -->
            <div class="card">
                <div class="card-body">
    <div class="container">
        <h2>Edit Result</h2>
        @if (session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif
        <form action="{{ route('admin.results.update', $result) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <div class="form-group">
                    <label for="user_id">Student</label>
                    <select name="user_id" for="form-control" required>
                        <option value="">Select Student</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" data-level="{{ $student->level }}" {{ $result->user_id == $student->id ? 'selected' : '' }}>{{ $student->name }} ({{ $student->matric_number }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="session">Session</label>
                <input type="text" name="session" class="form-control" value="{{ old('session', $result->session) }}" required>
            </div>
            <div class="form-group">
                <label for="semester">Semester</label>
                <select name="semester" class="form-control" required>
                    <option name="option value" value="First" {{ $result->semester == 'First' ? 'selected' : '' }}>First</option>
                    <option value="Second" {{ $result->semester == 'Second' ? 'selected' : '' }}>Second</option>
                </select>
            </div>
            <div class="form-group">
                <label for="level">Level</label>
                <input type="text" name="level" id="level" class="form-control" value="{{ old('level', $result->level) }}" readonly>
            </div>
            <div class="form-group">
                <label for="course_code">Course Code</label>
                <input type="text" name="course_code" class="form-control" value="{{ old('course_code', $result->course_code) }}" required>
            </div>
            <div class="form-group">
                <label for="course_title">Course Title</label>
                <input type="text" name="course_title" class="form-control" value="{{ old('course_title', $result->course_title) }}" required>
            </div>
            <div class="form-group">
                <label for="credit_unit">Credit Unit</label>
                <input type="number" name="credit_unit" class="form-control" value="{{ old('credit_unit', $result->credit_unit) }}" required>
            </div>
            <div class="form-group">
                <label for="score">Score</label>
                <input type="number" name="score" class="form-control" value="{{ old('score', $result->score) }}" step="0.01" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Result</button>
        </form>
    </div>
    <script>
        document.querySelector('select[name="user_id"]').addEventListener('change', function() {
            var level = this.options[this.selectedIndex].getAttribute('data-level');
            document.getElementById('level').value = level || '';
        });
        document.getElementById('level').value = document.querySelector('select[name="user_id"] option:selected').getAttribute('data-level') || '';
    </script>


                    
                </div>
            </div>

        </div>
        </div>
    </div>
@endsection
