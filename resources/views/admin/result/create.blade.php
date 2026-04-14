@extends('layouts.dash')

@section('content')
@php($routePrefix = auth()->user()->usertype === 'lecturer' ? 'lecturer' : 'admin')
@php($studentsEndpoint = url($routePrefix . '/results/get-students'))

<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-home"></i>
                </span> Add New Result
            </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span>Add New Result <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Registration Form -->
        <div class="card">
            <div class="card-body">
                <div class="container">
                    <h2>Add New Result</h2>
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
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
                    <form action="{{ route($routePrefix.'.results.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="course_id">Course</label>
                            <select name="course_id" id="course_id" class="form-control" required>
                                <option value="">Select Course</option>
                                @foreach ($courses as $course)
                                    <option
                                        value="{{ $course->id }}"
                                        data-code="{{ $course->code }}"
                                        data-title="{{ $course->title }}"
                                        data-credit-unit="{{ $course->credit_unit }}"
                                        data-department-id="{{ $course->department_id }}"
                                        {{ old('course_id') == $course->id ? 'selected' : '' }}
                                    >
                                        {{ $course->code }} - {{ $course->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">  
                            <label for="department_id">Department</label>
                            <select name="department_id" id="department_id" class="form-control" required>
                                <option value="">Select Department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="user_id">Student</label>
                            <select name="user_id" id="student_id" class="form-control" required>
                                <option value="">Select Student</option>
                                {{-- Dynamically filled --}}
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="session">Session</label>
                            <select name="session" id="session" class="form-control" required>
                                <option value="">Select Session</option>
                                @foreach ($academicSessions as $academicSession)
                                    <option value="{{ $academicSession }}" {{ old('session') === $academicSession ? 'selected' : '' }}>
                                        {{ $academicSession }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="semester">Semester</label>
                            <select name="semester" class="form-control" required>
                                <option value="First">First</option>
                                <option value="Second">Second</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="level">Level</label>
                            <input type="text" name="level" id="level" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="course_code">Course Code</label>
                            <input type="text" name="course_code" id="course_code" class="form-control" value="{{ old('course_code') }}" readonly>
                        </div>

                        <div class="form-group">
                            <label for="course_title">Course Title</label>
                            <input type="text" name="course_title" id="course_title" class="form-control" value="{{ old('course_title') }}" readonly>
                        </div>

                        <div class="form-group">
                            <label for="credit_unit">Credit Unit</label>
                            <input type="number" name="credit_unit" id="credit_unit" class="form-control" value="{{ old('credit_unit') }}" readonly>
                        </div>

                        <div class="form-group">
                            <label for="ca_score">CA</label>
                            <input type="number" name="ca_score" id="ca_score" class="form-control score-part" value="{{ old('ca_score') }}" step="0.01" min="0" max="100">
                        </div>

                        <div class="form-group">
                            <label for="exam_score">Exam</label>
                            <input type="number" name="exam_score" id="exam_score" class="form-control score-part" value="{{ old('exam_score') }}" step="0.01" min="0" max="100">
                        </div>

                        <div class="form-group">
                            <label for="score">Score</label>
                            <input type="number" name="score" id="score" class="form-control" value="{{ old('score') }}" step="0.01" min="0" max="100">
                            <small class="text-muted">If CA or Exam is entered, Score is calculated automatically. Leave CA and Exam blank to enter Score directly.</small>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Result</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
</div>
@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {

    // Load students dynamically when department changes
    function loadStudents(departmentId) {
        $('#student_id').html('<option value="">Loading...</option>');

        if (departmentId) {
            $.ajax({
                url: "{{ $studentsEndpoint }}/" + departmentId,
                type: 'GET',
                success: function (data) {
                    $('#student_id').empty().append('<option value="">Select Student</option>');
                    $.each(data, function (key, student) {
                        $('#student_id').append(
                            '<option value="' + student.id + '" data-level="' + student.level + '">' +
                            student.name + ' (' + student.matric_number + ')' +
                            '</option>'
                        );
                    });
                }
            });
        } else {
            $('#student_id').html('<option value="">Select Student</option>');
        }
    }

    $('#department_id').on('change', function () {
        loadStudents($(this).val());
    });

    // Auto-fill level when student is selected
    $('#student_id').on('change', function () {
        const level = this.options[this.selectedIndex].getAttribute('data-level');
        $('#level').val(level || '');
    });

    $('#course_id').on('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const departmentId = selectedOption.getAttribute('data-department-id') || '';

        $('#course_code').val(selectedOption.getAttribute('data-code') || '');
        $('#course_title').val(selectedOption.getAttribute('data-title') || '');
        $('#credit_unit').val(selectedOption.getAttribute('data-credit-unit') || '');
        $('#department_id').val(departmentId);
        loadStudents(departmentId);
    });

    function updateScoreField() {
        const caValue = $('#ca_score').val();
        const examValue = $('#exam_score').val();
        const hasCa = caValue !== '';
        const hasExam = examValue !== '';
        const scoreField = $('#score');

        if (hasCa || hasExam) {
            const total = (parseFloat(caValue || 0) + parseFloat(examValue || 0)).toFixed(2);
            scoreField.val(total).prop('readonly', true);
        } else {
            scoreField.prop('readonly', false);
        }
    }

    $('.score-part').on('input', updateScoreField);
    updateScoreField();
    $('#course_id').trigger('change');

});
</script>
