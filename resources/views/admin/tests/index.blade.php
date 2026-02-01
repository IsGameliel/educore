@extends('layouts.dash')

@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-home"></i>
                </span> Tests Management
            </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span>Manage Tests <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Tests Table Card -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title">List of Tests</h4>
                    <a href="{{ route('admin.tests.create') }}" class="btn btn-gradient-primary btn-sm">
                        <i class="mdi mdi-plus"></i> Create New Test
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Subject</th>
                                <th>Duration (mins)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tests as $test)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $test->name }}</td>
                                    <td>{{ $test->subject }}</td>
                                    <td>{{ $test->duration }}</td>
                                    <td>
                                        <span class="badge {{ $test->status ? 'bg-success' : 'bg-danger' }}">
                                            {{ $test->status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('admin.tests.questions', $test->id) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="mdi mdi-help"></i> Manage Questions
                                            </a>
                                            <a href="{{ route('admin.tests.responses', $test->id) }}"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="mdi mdi-eye"></i> View Responses
                                            </a>
                                            <a href="{{ route('admin.tests.edit', $test->id) }}"
                                               class="btn btn-sm btn-outline-info">
                                                <i class="mdi mdi-pencil"></i> Edit Test
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No tests found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@endsection
