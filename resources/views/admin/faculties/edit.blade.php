@extends('layouts.dash')

@section('content')

    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-home"></i>
                    </span> Update {{ $faculty->name}}
                </h3>
                <nav aria-label="breadcrumb">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            <span></span>Update Faculty <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Registration Form -->
            <div class="card">
                <div class="card-body">

                    <form action="{{ route('admin.faculties.update', $faculty->id ) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="code">Faculty name</label>
                            <input type="text" name="name" id="code" class="form-control" value="{{ $faculty->name }}" required>
                        </div>
                        <div class="form-group">
                            <label for="code">Faculty name</label>
                            <input type="text" name="description" id="code" class="form-control" value="{{ $faculty->description }}" required>
                        </div>
                        <button type="submit" class="btn btn-success">Update</button>
                    </form>
                </div>
            </div>
        </div>
        </div>
    </div>

@endsection
