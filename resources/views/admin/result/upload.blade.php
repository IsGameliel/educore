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
                <label for="user_id">Student</label>
                <select name="user_id" class="form-control" required>
                    <option value="">Select Student</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->matric_number }}) }}</option>
                    @endforeach
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
            user_id, matric_number, session, semester, level, course_code, course_title, credit_unit, score
        </p>
    </div>
</div>
  </div>
            </div>

        </div>
        </div>
    </div>
@endsection
