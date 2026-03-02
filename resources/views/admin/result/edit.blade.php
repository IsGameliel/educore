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
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('admin.results.update', $result) }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="department_id" value="{{ old('department_id', $result->department_id) }}">
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
                <label for="ca_score">CA</label>
                <input type="number" name="ca_score" id="ca_score" class="form-control score-part" value="{{ old('ca_score', $result->ca_score) }}" step="0.01" min="0" max="100">
            </div>
            <div class="form-group">
                <label for="exam_score">Exam</label>
                <input type="number" name="exam_score" id="exam_score" class="form-control score-part" value="{{ old('exam_score', $result->exam_score) }}" step="0.01" min="0" max="100">
            </div>
            <div class="form-group">
                <label for="score">Score</label>
                <input type="number" name="score" id="score" class="form-control" value="{{ old('score', $result->score) }}" step="0.01" min="0" max="100">
                <small class="text-muted">If CA or Exam is entered, Score is calculated automatically. Leave CA and Exam blank to edit Score directly.</small>
            </div>
            <button type="submit" class="btn btn-primary">Update Result</button>
        </form>
    </div>
    <script>
        const studentSelect = document.querySelector('select[name="user_id"]');
        const levelField = document.getElementById('level');
        const caField = document.getElementById('ca_score');
        const examField = document.getElementById('exam_score');
        const scoreField = document.getElementById('score');

        studentSelect.addEventListener('change', function() {
            var level = this.options[this.selectedIndex].getAttribute('data-level');
            levelField.value = level || '';
        });

        function updateScoreField() {
            const hasCa = caField.value !== '';
            const hasExam = examField.value !== '';

            if (hasCa || hasExam) {
                const total = (parseFloat(caField.value || 0) + parseFloat(examField.value || 0)).toFixed(2);
                scoreField.value = total;
                scoreField.readOnly = true;
            } else {
                scoreField.readOnly = false;
            }
        }

        caField.addEventListener('input', updateScoreField);
        examField.addEventListener('input', updateScoreField);

        levelField.value = studentSelect.options[studentSelect.selectedIndex].getAttribute('data-level') || '';
        updateScoreField();
    </script>


                    
                </div>
            </div>

        </div>
        </div>
    </div>
@endsection
