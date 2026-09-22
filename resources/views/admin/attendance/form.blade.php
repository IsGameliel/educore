@php
    $attendanceRoutePrefix = $attendanceRoutePrefix ?? 'admin.attendance';

    $scheduleLabel = function ($schedule) {
        $course = $schedule->course ? "{$schedule->course->code} - {$schedule->course->title}" : $schedule->subject;
        $department = optional($schedule->department)->name ?: 'No department';
        return "{$department} | {$schedule->level} Level | {$course} | {$schedule->day} {$schedule->start_time}";
    };

    $attendanceDate = $session->attendance_date;
    $attendanceDateValue = is_object($attendanceDate) && method_exists($attendanceDate, 'format')
        ? $attendanceDate->format('Y-m-d')
        : $attendanceDate;
@endphp

@csrf
@if($method ?? false)
    @method($method)
@endif

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="form-label">Class</label>
        <select name="class_schedule_id" class="form-select @error('class_schedule_id') is-invalid @enderror" onchange="if (!this.form.dataset.editing) { window.location='{{ route($attendanceRoutePrefix . '.create') }}?class_schedule_id=' + this.value + '&date=' + encodeURIComponent(document.querySelector('[name=attendance_date]').value); }">
            <option value="">Select Class</option>
            @foreach($classSchedules as $schedule)
                <option value="{{ $schedule->id }}" {{ old('class_schedule_id', $session->class_schedule_id) == $schedule->id ? 'selected' : '' }}>
                    {{ $scheduleLabel($schedule) }}
                </option>
            @endforeach
        </select>
        @error('class_schedule_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Date</label>
        <input type="date" name="attendance_date" value="{{ old('attendance_date', $attendanceDateValue) }}" class="form-control @error('attendance_date') is-invalid @enderror">
        @error('attendance_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3 d-flex align-items-end">
        @unless($method ?? false)
            <button type="submit" formaction="{{ route($attendanceRoutePrefix . '.store') }}" class="btn brand-btn w-100">
                <i class="mdi mdi-content-save"></i> Save Attendance
            </button>
        @else
            <button type="submit" class="btn brand-btn w-100">
                <i class="mdi mdi-content-save"></i> Update Attendance
            </button>
        @endunless
    </div>
</div>

<div class="mb-4">
    <label class="form-label">Notes</label>
    <textarea name="notes" rows="2" class="form-control" placeholder="Optional class note">{{ old('notes', $session->notes) }}</textarea>
</div>

@if($selectedSchedule)
    <div class="alert alert-light border">
        <strong>{{ optional($selectedSchedule->course)->code ?: $selectedSchedule->subject }}</strong>
        <span class="text-muted">| {{ optional($selectedSchedule->department)->name }} | {{ $selectedSchedule->level }} Level | {{ $selectedSchedule->day }} | {{ $selectedSchedule->start_time }} - {{ $selectedSchedule->end_time }}</span>
    </div>
@endif

<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Student</th>
                <th>Matric No.</th>
                <th>Status</th>
                <th>Late / Absent Reason</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                @php
                    $record = $records->get($student->id);
                    $status = old("attendance.{$student->id}.status", optional($record)->status ?: 'present');
                    $reason = old("attendance.{$student->id}.reason", optional($record)->reason);
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $student->name }}</td>
                    <td>{{ $student->matric_number ?: 'Not set' }}</td>
                    <td style="min-width:170px">
                        <select name="attendance[{{ $student->id }}][status]" class="form-select form-select-sm">
                            @foreach($statuses as $option)
                                <option value="{{ $option }}" {{ $status === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" name="attendance[{{ $student->id }}][reason]" value="{{ $reason }}" class="form-control form-control-sm" placeholder="Reason when late, absent, or excused">
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        Select a class to load students, or add students to the schedule's department and level.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
