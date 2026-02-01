@extends('layouts.dash')

@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-home"></i>
                </span> Add Staff
            </h3>
        </div>

        <!-- Registration Form -->
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.staffs.store') }}" method="POST">
                    @csrf

                        <!-- Full Name -->
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                                @error('name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                        <!-- Email -->
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
                                @error('email')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                        <!-- usertype -->
                            <div class="form-group">
                                <label for="usertype">Position</label>
                                <select name="usertype" id="usertype" class="form-control" required>
                                    <option value="">Choose usertype</option>
                                    <option value="guest" {{ old('usertype') == 'guest' ? 'selected' : '' }}>Guest</option>
                                    <option value="lecturer" {{ old('usertype') == 'lecturer' ? 'selected' : '' }}>Lecturer</option>
                                    <option value="vc" {{ old('usertype') == 'vc' ? 'selected' : '' }}>Vice Chancellor</option>
                                    <option value="registrar" {{ old('usertype') == 'registrar' ? 'selected' : '' }}>Registrar</option>
                                    <option value="burser" {{ old('usertype') == 'burser' ? 'selected' : '' }}>Burser</option>
                                    <option value="admin" {{ old('usertype') == 'admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                                @error('usertype')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                        <!-- Password -->
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                                @error('password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                        <!-- Confirm Password -->
                            <div class="form-group">
                                <label for="password_confirmation">Confirm Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                                @error('password_confirmation')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                    <button type="submit" class="btn btn-success">Add Staff</button>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>

@endsection
