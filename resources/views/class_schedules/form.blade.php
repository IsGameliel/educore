<div class="form-group">
    <label for="department">Department</label>
    <select name="department_id" id="department" class="form-control" required>
        <option value="">Select Department</option>
        @foreach($departments as $department)
            <option value="{{ $department->id }}" {{ (old('department_id') == $department->id || isset($schedule) && $schedule->department_id == $department->id) ? 'selected' : '' }}>
                {{ $department->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="level">Level</label>
    <select name="level" id="level" class="form-control" required>
        <option value="">Select Level</option>
        @foreach($levels as $level)
            <option value="{{ $level }}" {{ (old('level') == $level || isset($schedule) && $schedule->level == $level) ? 'selected' : '' }}>
                {{ $level }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="semester">Semester</label>
    @php
        $selectedSemester = old('semester', $schedule->semester ?? '');
        $selectedSemester = strtolower(trim($selectedSemester)) === 'first semester' ? 'First' : (strtolower(trim($selectedSemester)) === 'second semester' ? 'Second' : $selectedSemester);
    @endphp
    <select name="semester" id="semester" class="form-control" required>
        <option value="">Select Semester</option>
        @foreach($semesters as $semester)
            <option value="{{ $semester }}" {{ $selectedSemester == $semester ? 'selected' : '' }}>
                {{ $semester }} Semester
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="subject">Course</label>
    <select name="subject" id="subject" class="form-control" required style="width: 100%;">
        <option value="">Select Course</option>
        @if(isset($schedule) && $schedule->subject)
            @php
                $currentCourse = $courses->firstWhere('id', $schedule->subject);
            @endphp
            @if($currentCourse)
                <option value="{{ $currentCourse->id }}" selected>
                    {{ $currentCourse->code }} - {{ $currentCourse->title }}
                </option>
            @endif
        @endif
    </select>
</div>

<div class="form-group">
    <label for="lecturer">Lecturer</label>
    <select name="lecturer_id" id="lecturer" class="form-control" required>
        <option value="">Select Lecturer</option>
        @foreach($lecturers as $lecturer)
            <option value="{{ $lecturer->id }}" {{ (old('lecturer_id') == $lecturer->id || (isset($schedule) && $schedule->lecturer_id == $lecturer->id)) ? 'selected' : '' }}>
                {{ $lecturer->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="day">Day</label>
    <select name="day" id="day" class="form-control" required>
        <option value="">Select Day</option>
        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
            <option value="{{ $day }}" {{ (old('day') == $day || isset($schedule) && $schedule->day == $day) ? 'selected' : '' }}>
                {{ $day }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label>Start Time</label>
    <input type="time" name="start_time" value="{{ $schedule->start_time ?? old('start_time') }}" class="form-control" required>
</div>

<div class="form-group">
    <label>End Time</label>
    <input type="time" name="end_time" value="{{ $schedule->end_time ?? old('end_time') }}" class="form-control" required>
</div>

<div class="form-group">
    <label>Room</label>
    <input type="text" name="room" value="{{ $schedule->room ?? old('room') }}" class="form-control" required>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const department = document.getElementById('department');
    const level = document.getElementById('level');
    const semester = document.getElementById('semester');
    const subject = document.getElementById('subject');
    const selectedCourseId = @json((string) old('subject', $schedule->subject ?? ''));
    const coursesUrl = @json(route('admin.class-schedules.courses.filtered'));

    function resetCourses(label = 'Select Course') {
        subject.replaceChildren(new Option(label, ''));
    }

    function loadFilteredCourses() {
        const departmentId = department.value;
        const selectedLevel = level.value;
        const selectedSemester = semester.value;

        if (!departmentId || !selectedLevel || !selectedSemester) {
            resetCourses();
            return;
        }

        resetCourses('Loading courses...');

        const params = new URLSearchParams({
            department_id: departmentId,
            level: selectedLevel,
            semester: selectedSemester,
        });

        fetch(`${coursesUrl}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error(`Course request failed with status ${response.status}`);
                }

                return response.json();
            })
            .then(function(courses) {
                resetCourses(courses.length ? 'Select Course' : 'No courses found');

                courses.forEach(function(course) {
                    const option = new Option(`${course.code} - ${course.title}`, course.id, false, String(course.id) === selectedCourseId);
                    subject.appendChild(option);
                });
            })
            .catch(function(error) {
                console.error('Error loading courses:', error);
                resetCourses('Error loading courses');
            });
    }

    [department, level, semester].forEach(function(field) {
        field.addEventListener('change', loadFilteredCourses);
    });

    if (department.value && level.value && semester.value) {
        loadFilteredCourses();
    }
});
</script>
@endsection
