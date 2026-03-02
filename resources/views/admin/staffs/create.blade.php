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
                                    <option value="exam_officer" {{ old('usertype') == 'exam_officer' ? 'selected' : '' }}>Exam Officer</option>
                                    <option value="vc" {{ old('usertype') == 'vc' ? 'selected' : '' }}>Vice Chancellor</option>
                                    <option value="registrar" {{ old('usertype') == 'registrar' ? 'selected' : '' }}>Registrar</option>
                                    <option value="burser" {{ old('usertype') == 'burser' ? 'selected' : '' }}>Burser</option>
                                    <option value="admin" {{ old('usertype') == 'admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                                @error('usertype')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" id="course-assignment-group" style="{{ old('usertype') === 'lecturer' ? '' : 'display: none;' }}">
                                <label for="course_ids">Assigned Courses</label>
                                <select name="course_ids[]" id="course_ids" class="form-control" multiple size="8">
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}" {{ in_array($course->id, old('course_ids', [])) ? 'selected' : '' }}>
                                            {{ $course->code }} - {{ $course->title }} ({{ $course->department->name ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Assign one or more courses when the staff member is a lecturer.</small>
                                @error('course_ids')
                                    <span class="text-danger d-block">{{ $message }}</span>
                                @enderror
                                @error('course_ids.*')
                                    <span class="text-danger d-block">{{ $message }}</span>
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const staffTypeSelect = document.getElementById('usertype');
        const courseAssignmentGroup = document.getElementById('course-assignment-group');

        if (!staffTypeSelect || !courseAssignmentGroup) {
            return;
        }

        function toggleCourseAssignment() {
            courseAssignmentGroup.style.display = staffTypeSelect.value === 'lecturer' ? 'block' : 'none';
        }

        staffTypeSelect.addEventListener('change', toggleCourseAssignment);
        toggleCourseAssignment();
    });
</script>
