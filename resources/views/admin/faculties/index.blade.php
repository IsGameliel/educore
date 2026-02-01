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
                    <a href="{{ route('admin.faculties.create') }}" class="btn btn-primary">Add New Faculty</a>
                    <a href="{{ route('admin.faculties.import.form') }}" class="btn btn-primary">Import Faculties</a>

                    <!-- Table container for horizontal scrolling -->
                    <div style="overflow-x: auto; margin-top: 20px;">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>SN</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($faculties as $faculty)
                                    <tr>
                                        <td>{{ $faculty->id }}</td>
                                        <td>{{ $faculty->name }}</td>
                                        <td>{{ $faculty->description }}</td>
                                        <td>
                                            <a href="{{ route('admin.faculties.edit', $faculty->id) }}" class="btn btn-warning">Edit</a>
                                            <form action="{{ route('admin.faculties.destroy', $faculty->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
        </div>
    </div>
@endsection
