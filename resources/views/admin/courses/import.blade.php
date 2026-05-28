@extends('layouts.dash')

@section('content')

    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-home"></i>
                    </span> Import Courses
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
                    @if(session('success'))
                            <p style="color: green">{{ session('success') }}</p>
                    @endif
                    @if(session('warning'))
                            <p style="color: #b45309">{{ session('warning') }}</p>
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

                    <div class="alert alert-info">
                        <strong>Expected columns:</strong>
                        <code>code, title, credit_unit, semester, level, department_ids</code>
                        <br>
                        <small>
                            Use <code>department_ids</code> for one or many departments in the same row, for example
                            <code>1,4,7</code>. A single <code>department_id</code> column is also still supported.
                        </small>
                    </div>

                    <form action="{{ route('admin.courses.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="name">Course Import File</label>
                            <input type="file" name="file" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success">Save</button>
                    </form>

                    @if(session('import_errors'))
                        <div class="mt-4">
                            <h5>Rows That Need Attention</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Row</th>
                                            <th>Course Code</th>
                                            <th>Issue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(session('import_errors') as $errorRow)
                                            <tr>
                                                <td>{{ $errorRow['row'] }}</td>
                                                <td>{{ $errorRow['code'] }}</td>
                                                <td>{{ implode(' ', $errorRow['errors']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
        </div>
    </div>

@endsection
