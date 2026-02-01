@extends('layouts.dash')

@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-home"></i>
                </span> Edit Results Group
            </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span>Edit Results for {{ $session }} {{ $semester }} <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Edit Group Form -->
        <div class="card">
            <div class="card-body">
                <div class="container">
                    <h2>Edit Results for {{ $students->find($user_id)->name ?? 'Student' }} ({{ $session }} {{ $semester }})</h2>
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <form action="{{ route('admin.results.updateGroup', [$user_id, $session, $semester]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Credit Unit</th>
                                        <th>Score</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($results as $result)
                                    <tr>
                                        <td>
                                            <input type="text" name="results[{{ $result->id }}][course_code]" class="form-control" value="{{ $result->course_code }}" required>
                                        </td>
                                        <td>
                                            <input type="text" name="results[{{ $result->id }}][course_title]" class="form-control" value="{{ $result->course_title }}" required>
                                        </td>
                                        <td>
                                            <input type="number" name="results[{{ $result->id }}][credit_unit]" class="form-control" value="{{ $result->credit_unit }}" required>
                                        </td>
                                        <td>
                                            <input type="number" name="results[{{ $result->id }}][score]" class="form-control" value="{{ $result->score }}" step="0.01" required>
                                        </td>
                                        <td>
                                            <input type="text" name="results[{{ $result->id }}][grade]" class="form-control" value="{{ $result->grade }}" required>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Update Results</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection