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
                        <label for="usertype" class="form-label">User Tyoe</label>
                        <select name="usertype" id="usertype" class="form-control" required>
                            <option value="bursar" {{ old('usertype', $staff->usertype) == 'bursar' ? 'selected' : '' }}>Bursar</option>
                            <option value="registrar" {{ old('usertype', $staff->usertype) == 'registrar' ? 'selected' : '' }}>Registrar</option>
                            <option value="vc" {{ old('usertype', $staff->usertype) == 'vc' ? 'selected' : '' }}>Vice Chancellor</option>
                            <option value="admin" {{ old('usertype', $staff->usertype) == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="exam_officer" {{ old('usertype', $staff->usertype) == 'exam_officer' ? 'selected' : '' }}>Exam Officer</option>
                            <option value="lecturer" {{ old('usertype', $staff->usertype) == 'lecturer' ? 'selected' : '' }}>Lecturer</option>
                            <option value="guest" {{ old('usertype', $staff->usertype) == 'guest' ? 'selected' : '' }}>Guest</option>
                            <option value="student" {{ old('usertype', $staff->usertype) == 'student' ? 'selected' : '' }}>Student</option>
                        </select>
                    </div>

                    <div class="mb-3" id="course-assignment-group" style="{{ old('usertype', $staff->usertype) === 'lecturer' ? '' : 'display: none;' }}">
                        <label for="course_ids" class="form-label">Assigned Courses</label>
                        @php
                            $selectedCourses = old('course_ids', $staff->assignedCourses->pluck('id')->all());
                        @endphp
                        <select name="course_ids[]" id="course_ids" class="form-control" multiple size="8">
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" {{ in_array($course->id, $selectedCourses) ? 'selected' : '' }}>
                                    {{ $course->code }} - {{ $course->title }} ({{ $course->department->name ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Only lecturers can manage assigned course results.</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>

@endsection
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editStaffTypeSelect = document.getElementById('usertype');
        const editCourseAssignmentGroup = document.getElementById('course-assignment-group');

        if (!editStaffTypeSelect || !editCourseAssignmentGroup) {
            return;
        }

        function toggleEditCourseAssignment() {
            editCourseAssignmentGroup.style.display = editStaffTypeSelect.value === 'lecturer' ? 'block' : 'none';
        }

        editStaffTypeSelect.addEventListener('change', toggleEditCourseAssignment);
        toggleEditCourseAssignment();
    });
</script>
