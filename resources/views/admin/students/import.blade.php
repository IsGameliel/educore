@extends('layouts.dash')

@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-upload"></i>
                    </span> Import Students
                </h3>
                <nav aria-label="breadcrumb">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            <span></span>Bulk create student accounts <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="card">
                <div class="card-body">
                    @if(session('warning'))
                        <div class="alert alert-warning">{{ session('warning') }}</div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-4">
                        <h5>Expected file columns</h5>
                        <p class="mb-2">Upload an Excel or CSV file with these heading names on the first row:</p>
                        <code>name, email, matric_number, level, department_id</code>
                        <p class="mt-3 mb-0 text-muted">Example level values: 100, 200, 300, 400, 500, 600.</p>
                    </div>

                    <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="file">Student Import File</label>
                            <input type="file" name="file" id="file" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success">Import Students</button>
                        <a href="{{ route('admin.students.index') }}" class="btn btn-light">Back</a>
                    </form>

                    @if(session('import_errors'))
                        <div class="mt-4">
                            <h5>Rows With Issues</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Row</th>
                                            <th>Email</th>
                                            <th>Issue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(session('import_errors') as $rowError)
                                            <tr>
                                                <td>{{ $rowError['row'] }}</td>
                                                <td>{{ $rowError['email'] }}</td>
                                                <td>{{ implode(' ', $rowError['errors']) }}</td>
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
