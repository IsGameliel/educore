@extends('layouts.dash')

@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-settings"></i>
                    </span> Department Pass Marks
                </h3>
                <nav aria-label="breadcrumb">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            <span></span>Set or update the minimum pass score for each department
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.departments.passmarks.update') }}" method="POST">
                        @csrf
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Pass Mark</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($departments as $dept)
                                    <tr>
                                        <td>{{ $dept->name }}</td>
                                        <td>
                                            <input type="number" name="pass_marks[{{ $dept->id }}]"
                                                class="form-control" min="0" max="100"
                                                value="{{ old('pass_marks.' . $dept->id, $dept->pass_mark ?? 40) }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button class="btn btn-primary mt-3" type="submit">Save Pass Marks</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
