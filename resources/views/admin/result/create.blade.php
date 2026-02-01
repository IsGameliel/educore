@extends('layouts.dash')

@section('content')

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
                    <form action="{{ route('admin.results.store') }}" method="POST">
                        @csrf
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
                            <input type="text" name="session" class="form-control" value="{{ old('session', '2023/2024') }}" required>
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
                            <input type="text" name="course_code" class="form-control" value="{{ old('course_code') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="course_title">Course Title</label>
                            <input type="text" name="course_title" class="form-control" value="{{ old('course_title') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="credit_unit">Credit Unit</label>
                            <input type="number" name="credit_unit" class="form-control" value="{{ old('credit_unit') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="score">Score</label>
                            <input type="number" name="score" class="form-control" value="{{ old('score') }}" step="0.01" required>
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
    $('#department_id').on('change', function () {
        let departmentId = $(this).val();
        $('#student_id').html('<option value="">Loading...</option>');

        if (departmentId) {
            $.ajax({
                url: "{{ url('/admin/results/get-students') }}/" + departmentId,
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
    });

    // Auto-fill level when student is selected
    $('#student_id').on('change', function () {
        const level = this.options[this.selectedIndex].getAttribute('data-level');
        $('#level').val(level || '');
    });

});
</script>
