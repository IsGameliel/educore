@extends('layouts.dash')

@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-home"></i>
                </span> {{$course->title}}
            </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span>Update Course <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Registration Form -->
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.courses.update', $course->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="code">Course Code</label>
                        <input type="text" name="code" id="code" class="form-control" value="{{ $course->code }}" required>
                    </div>

                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" name="title" id="title" class="form-control" value="{{$course->title}}" required>
                    </div>

                    <div class="form-group">
                        <label for="credit_unit">Credit Unit</label>
                        <input type="number" name="credit_unit" id="credit_unit" class="form-control" value="{{$course->credit_unit}}" required>
                    </div>

                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select name="semester" id="semester" class="form-control" required>
                            <option value="First" {{ $course->semester == 'First' ? 'selected' : ''}}>First semester</option>
                            <option value="Second" {{ $course->semester == 'Second' ? 'selected' : '    '}}>Second semester</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="semester">Level</label>
                        <select name="level" id="level" class="form-control" required>
                            <option value="100" {{ $course->level == 'First' ? 'selected' : '' }}>100 Level</option>
                            <option value="200" {{ $course->level == 'Second' ? 'selected' : '' }}>200 Level</option>
                            <option value="300" {{ $course->level == 'Third' ? 'selected' : '' }}>300 Level</option>
                            <option value="400" {{ $course->level == 'Fourth' ? 'selected' : '' }}>400 Level</option>
                            <option value="500" {{ $course->level == 'Fifth' ? 'selected' : '' }}>500 Level</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="department_id">Department</label>
                        <select name="department_id" id="department_id" class="form-control" required>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ $course->department_id == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
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
