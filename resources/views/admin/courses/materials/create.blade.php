@extends('layouts.dash')

@section('content')

    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-home"></i>
                    </span> Add Course materials
                </h3>
                <nav aria-label="breadcrumb">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            <span></span>Add Lecture Materials <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Registration Form -->
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.course-materials.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="level">Level</label>
                            <select name="level" id="level" class="form-control" required>
                                <option value="">Choose Level</option>
                                <option value="100">100 Level</option>
                                <option value="200">200 Level</option>
                                <option value="300">300 Level</option>
                                <option value="400">400 Level</option>
                                <option value="500">500 Level</option>
                            </select>
                        </div>
                        @include('admin.courses.materials.course-fields')
                    <div class="form-group">
                            <label for="file">Upload Course Material (PDF)</label>
                            <input type="file" name="file" id="file" class="form-control" accept="application/pdf" required>
                        </div>
                        <div class="form-group">
                            <label for="cover_photo">Cover Photo</label>
                            <input type="file" name="cover_photo" id="cover_photo" class="form-control" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-success">Save</button>
                    </form>
                </div>
            </div>
        </div>
        </div>
    </div>

@endsection
