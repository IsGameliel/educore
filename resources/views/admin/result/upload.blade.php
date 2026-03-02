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
        <h2>Upload Results (CSV/Excel)</h2>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <form action="{{ route('admin.results.storeUpload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="course_id">Course</label>
                <select name="course_id" id="course_id" class="form-control" required>
                    <option value="">Select Course</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" data-department-id="{{ $course->department_id }}">
                            {{ $course->code }} - {{ $course->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="user_id">Student</label>
                <select name="user_id" id="student_id" class="form-control" required>
                    <option value="">Select Student</option>
                </select>
            </div>
            <div class="form-group">
                <label for="file">Upload Result File (CSV/Excel)</label>
                <input type="file" name="file" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>
        <p class="mt-3">
            <strong>Note:</strong> The CSV/Excel file should have columns: 
            user_id, matric_number, session, semester, level, course_code, course_title, credit_unit, department_id,
            and either score or ca_score + exam_score. The selected course must match the uploaded rows.
        </p>
    </div>
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
    function loadStudents(departmentId) {
        $('#student_id').html('<option value="">Loading...</option>');

        if (!departmentId) {
            $('#student_id').html('<option value="">Select Student</option>');
            return;
        }

        $.ajax({
            url: "{{ url('/admin/results/get-students') }}/" + departmentId,
            type: 'GET',
            success: function (data) {
                $('#student_id').empty().append('<option value="">Select Student</option>');
                $.each(data, function (key, student) {
                    $('#student_id').append(
                        '<option value="' + student.id + '">' +
                        student.name + ' (' + student.matric_number + ')' +
                        '</option>'
                    );
                });
            }
        });
    }

    $('#course_id').on('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        loadStudents(selectedOption.getAttribute('data-department-id') || '');
    });
});
</script>
