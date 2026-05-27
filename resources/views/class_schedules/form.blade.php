<div class="form-group">
    <label for="department">Department</label>
    <select name="department_id" id="department" class="form-control" required>
        <option value="">Select Department</option>
        @foreach($departments as $department)
            <option value="{{ $department->id }}" {{ (old('department_id') == $department->id || isset($schedule) && $schedule->department == $department->id) ? 'selected' : '' }}>
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
    <label>Semester</label>
    <input type="text" name="semester" value="{{ $schedule->semester ?? old('semester') }}" class="form-control" required>
</div>
<div class="form-group">
    <label for="subject">Course</label>
    <select name="subject" id="subject" class="form-control" required>
        <option value="">Select Course</option>
        @foreach($courses as $course)
            <option value="{{ $course->id }}" {{ (old('subject') == $course->id || isset($schedule) && $schedule->title == $course->id) ? 'selected' : '' }}>
                {{ $course->title }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="lecturer">Lecturer</label>
    <select name="lecturer_id" id="lecturer" class="form-control" required>
        <option value="">Select Lecturer</option>
        @foreach($lecturers as $lecturer)
            <option value="{{ $lecturer->id }}" {{ (old('lecturer_id') == $lecturer->id || (isset($schedule) && $schedule->lecturer_id == $lecturer->id)) ? 'selected' : '' }}>
                {{ $lecturer->name }}  <!-- assuming 'name' is a field in the User model -->
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
