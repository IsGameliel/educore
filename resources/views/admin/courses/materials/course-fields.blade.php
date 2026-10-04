<div class="form-group">
    <label for="department_id">Department</label>
    <select name="department_id" id="department_id" class="form-control" required>
        <option value="">Choose Department</option>
        @foreach($departments as $department)
            <option value="{{ $department->id }}" @selected(old('department_id', $courseMaterial->department_id ?? '') == $department->id)>{{ $department->name }}</option>
        @endforeach
    </select>
    @error('department_id')<div class="text-danger">{{ $message }}</div>@enderror
</div>
<div class="form-group">
    <label for="semester">Semester</label>
    <select name="semester" id="semester" class="form-control" required>
        <option value="">Choose Semester</option>
        @foreach(['First', 'Second'] as $semester)
            <option value="{{ $semester }}" @selected(old('semester', $courseMaterial->semester ?? '') === $semester)>{{ $semester }}</option>
        @endforeach
    </select>
    @error('semester')<div class="text-danger">{{ $message }}</div>@enderror
</div>
<div class="form-group">
    <label for="course_id">Course</label>
    <select name="course_id" id="course_id" class="form-control" required>
        <option value="">Choose Course</option>
        @foreach($courses as $course)
            <option value="{{ $course->id }}" data-department="{{ $course->department_id }}" data-semester="{{ $course->semester }}" @selected(old('course_id', $courseMaterial->course_id ?? '') == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
        @endforeach
    </select>
    @error('course_id')<div class="text-danger">{{ $message }}</div>@enderror
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const department = document.getElementById('department_id');
        const semester = document.getElementById('semester');
        const course = document.getElementById('course_id');
        const options = Array.from(course.options).filter(option => option.value);

        function filterCourses() {
            const selected = course.value;
            const ready = department.value && semester.value;
            const matches = ready ? options.filter(option =>
                option.dataset.department === department.value && option.dataset.semester === semester.value
            ) : [];
            course.replaceChildren(new Option(!ready ? 'Choose department and semester first' :
                (matches.length ? 'Choose Course' : 'No courses match this department and semester'), ''));
            matches.forEach(option => course.add(option.cloneNode(true)));
            course.value = matches.some(option => option.value === selected) ? selected : '';
        }

        department.addEventListener('change', filterCourses);
        semester.addEventListener('change', filterCourses);
        filterCourses();
    });
</script>
