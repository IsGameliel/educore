@extends('layouts.dash')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title"> Edit Course Material </h3>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.course-materials.update', $courseMaterial->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" name="title" id="title" class="form-control" value="{{ $courseMaterial->title }}" required>
                    </div>

                    <div class="form-group">
                        <label for="level">Level</label>
                        <select name="level" id="level" class="form-control" required>
                            <option value="100" {{ $courseMaterial->level == 'First' ? 'selected' : '' }}>100 Level</option>
                            <option value="200" {{ $courseMaterial->level == 'Second' ? 'selected' : '' }}>200 Level</option>
                            <option value="300" {{ $courseMaterial->level == 'Third' ? 'selected' : '' }}>300 Level</option>
                            <option value="400" {{ $courseMaterial->level == 'Fourth' ? 'selected' : '' }}>400 Level</option>
                            <option value="500" {{ $courseMaterial->level == 'Fifth' ? 'selected' : '' }}>500 Level</option>
                        </select>
                    </div>

                    @include('admin.courses.materials.course-fields')
                    <div class="form-group">
                        <label for="file">Upload New PDF (Optional)</label>
                        <input type="file" name="file" id="file" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="cover_photo">Upload New Cover Photo (Optional)</label>
                        <input type="file" name="cover_photo" id="cover_photo" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-success">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
