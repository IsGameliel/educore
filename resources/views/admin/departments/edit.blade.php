@extends('layouts.dash')

@section('content')

    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-home"></i>
                    </span> Edit {{ $department->name}}
                </h3>
                <nav aria-label="breadcrumb">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            <span></span>Update Department <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Registration Form -->
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.departments.update', $department->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="name">Department Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ $department->name }}" required>
                        </div>
                        <div class="form-group">
                            <label for="code">Department Description</label>
                            <input type="text" name="description" id="description" class="form-control" value="{{ $department->description }}" required>
                        </div>
                        <div class="form-group">
                            <label for="faculty_id">Faculty</label>
                            <select name="faculty_id" id="faculty_id" class="form-control" required>
                                @foreach($faculties as $faculty)
                                    <option value="{{ $faculty->id }}" {{ $faculty->id == $department->faculty_id ? 'selected' : '' }}>
                                        {{ $faculty->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">Update</button>
                    </form>
            
                </div>
            </div>
        </div>
        </div>
    </div>

@endsection
