@extends('layouts.dash')

@section('content')

    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-home"></i>
                    </span> Course Registration
                </h3>
                <nav aria-label="breadcrumb">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            <span></span>Register for Courses <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Registration Form -->
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        @foreach ($courseMaterials as $courseMaterial)
                            <div class="col-md-3">
                                <div class="cover_photo">
                                    @if($courseMaterial->cover_photo && $courseMaterial->file_path)
                                    <a href="{{ asset('storage/' . $courseMaterial->file_path) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $courseMaterial->cover_photo) }}" alt="Course Cover Photo" style="width: 100%; object-fit: cover;">
                                    </a>
                                    @elseif($courseMaterial->cover_photo)
                                        <img src="{{ asset('storage/' . $courseMaterial->cover_photo) }}" alt="Course Cover Photo" style="width: 100%; object-fit: cover;">
                                    @else
                                        <span>No photo</span>
                                    @endif
                                </div>
                                <h5 class="mt-4">{{ $courseMaterial->title }}</h5>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>

@endsection
