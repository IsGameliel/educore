@extends('layouts.dash')

<style>
    /* Style the entire pagination container */
    .pagination-container {
        text-align: center;
        margin-top: 20px;
    }

    /* Style the pagination links (Next, Previous) */
    .pagination .page-item {
        margin: 0 5px;
    }

    /* Customize the active page link */
    .pagination .page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
        font-weight: bold;
    }

    /* Style the "Next" and "Previous" buttons */
    .pagination .page-link {
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        color: #007bff;
        padding: 10px 15px;
        border-radius: 4px;
        font-size: 16px;
        transition: background-color 0.3s ease;
    }

    /* Hover effect for the page links */
    .pagination .page-link:hover {
        background-color: #e2e6ea;
        color: #0056b3;
    }

    /* Disabled page links (e.g., when there's no next or previous page) */
    .pagination .page-item.disabled .page-link {
        background-color: #f8f9fa;
        color: #ccc;
        pointer-events: none;
    }

    /* Customize "Next" and "Previous" buttons */
    .pagination .page-item:first-child .page-link {
        border-top-left-radius: 20px;
        border-bottom-left-radius: 20px;
        width: 5% !important;
    }

    .pagination .page-item:last-child .page-link {
        border-top-right-radius: 20px;
        border-bottom-right-radius: 20px;
    }

    .pagination-container img, svg{
        width: 5% !important;
    }

</style>

@section('content')

    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-home"></i>
                    </span> Staffs
                </h3>
                <nav aria-label="breadcrumb">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            <span></span>All Staffs <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Registration Form -->
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('admin.staffs.create') }}" class="btn btn-primary">Add New Staff</a>

                    <!-- Table container for horizontal scrolling -->
                    <div style="overflow-x: auto; margin-top: 20px;">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Usertype</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staffs as $staff)
                                    <tr>
                                        <td>{{ $staff->name }}</td>
                                        <td>{{ $staff->usertype }}</td>
                                        <td>{{ $staff->email }}</td>
                                        <td>
                                            <a href="{{ route('admin.staffs.edit', $staff->id) }}" class="btn btn-warning">Edit</a>
                                            <form action="{{ route('admin.staffs.destroy', $staff->id) }}" method="POST" class="d-inline">
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
                    <div class="pagination-container">
                        {{ $staffs->links() }}
                    </div>
                </div>
            </div>

        </div>
        </div>
    </div>
@endsection
