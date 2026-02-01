@extends('layouts.dash')

@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-home"></i>
                </span> Update staff
            </h3>
        </div>

        <!-- Registration Form -->
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.staffs.update', $staff->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $staff->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $staff->email) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control">
                        <small class="form-text text-muted">Leave blank to keep the current password.</small>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="level" class="form-label">User Tyoe</label>
                        <select name="usertype" id="level" class="form-control" required>
                            <option value="bursar" {{ old('usertype', $staff->usertype) == 'bursar' ? 'selected' : '' }}>Bursar</option>
                            <option value="registrar" {{ old('usertype', $staff->usertype) == 'registrar' ? 'selected' : '' }}>Registrar</option>
                            <option value="vc" {{ old('level', $staff->usertype) == 'vc' ? 'selected' : '' }}>Vice Chancellor</option>
                            <option value="admin" {{ old('usertype', $staff->usertype) == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="lecturer" {{ old('usertype', $staff->usertype) == 'lecturer' ? 'selected' : '' }}>Lecturer</option>
                            <option value="guest" {{ old('usertype', $staff->usertype) == 'guest' ? 'selected' : '' }}>guest</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>

@endsection
