<?php

use App\Models\{AcademicSession, AttendanceRecord, AttendanceSession, ClassSchedule, CourseRegistration, Courses, Department, Faculty, Result, User};
use App\Services\Academic\{RegistrationAssistance, TimetableConflicts};
use Illuminate\Support\Facades\{DB, Mail};

beforeEach(function () {
    Mail::fake();
    $this->session = AcademicSession::create(['name'=>'2026/2027','start_year'=>2026,'end_year'=>2027,'is_active'=>true]);
    $faculty = Faculty::create(['name'=>'Science','code'=>'SCI']);
    $this->department = Department::create(['name'=>'Computing','faculty_id'=>$faculty->id]);
    $this->admin = User::factory()->create(['usertype'=>'admin']);
    $this->lecturer = User::factory()->create(['usertype'=>'lecturer']);
    $this->student = User::factory()->create(['usertype'=>'student','department_id'=>$this->department->id,'level'=>'100','entry_year'=>2026]);
    $this->course = Courses::create(['code'=>'CSC101','title'=>'Computing','credit_unit'=>3,'department_id'=>$this->department->id,'semester'=>'First','level'=>'100','academic_session_id'=>$this->session->id]);
    $this->slot = ['department_id'=>$this->department->id,'level'=>'100','semester'=>'First','subject'=>$this->course->id,'lecturer_id'=>$this->lecturer->id,'day'=>'Monday','start_time'=>'09:00','end_time'=>'10:00','room'=>'Hall A'];
});

it('blocks overlapping timetable bookings on create and edit but allows adjacent meetings', function () {
    $this->actingAs($this->admin)->get(route('admin.class-schedules.create'))->assertOk()->assertSee('Overlapping room');
    $this->getJson(route('admin.class-schedules.courses.filtered',['department_id'=>$this->department->id,'level'=>'100','semester'=>'First']))->assertOk()->assertJsonPath('0.academic_session.name','2026/2027');
    $this->actingAs($this->admin)->post(route('admin.class-schedules.store'),$this->slot)->assertSessionHasNoErrors();
    $this->post(route('admin.class-schedules.store'),array_replace($this->slot,['start_time'=>'09:30','end_time'=>'10:30']))->assertSessionHasErrors('start_time');
    $this->post(route('admin.class-schedules.store'),array_replace($this->slot,['start_time'=>'10:00','end_time'=>'11:00']))->assertSessionHasNoErrors();
    $id = ClassSchedule::latest('id')->first()->id;
    $this->put(route('admin.class-schedules.update',$id),array_replace($this->slot,['start_time'=>'09:30','end_time'=>'10:30']))->assertSessionHasErrors('start_time');
    expect(ClassSchedule::count())->toBe(2);
});

it('separates timetable sessions and validates course cohort and lecturer', function () {
    app(TimetableConflicts::class)->save($this->slot);
    $next = AcademicSession::create(['name'=>'2027/2028','start_year'=>2027,'end_year'=>2028]);
    $course = $this->course->replicate(); $course->academic_session_id=$next->id; $course->save();
    $this->actingAs($this->admin)->post(route('admin.class-schedules.store'),array_replace($this->slot,['subject'=>$course->id]))->assertSessionHasNoErrors();
    $this->post(route('admin.class-schedules.store'),array_replace($this->slot,['level'=>'200']))->assertSessionHasErrors('subject');
    $this->post(route('admin.class-schedules.store'),array_replace($this->slot,['lecturer_id'=>$this->student->id]))->assertSessionHasErrors('lecturer_id');
});

it('shows registration assistance without submitting courses and blocks student timetable clashes', function () {
    $other = $this->course->replicate(); $other->code='CSC102'; $other->save();
    $other->prerequisites()->attach($this->course->id);
    $help = RegistrationAssistance::forStudent($this->student);
    expect($help['First']['courses']->firstWhere('code','CSC102')['missing'])->toBe(['CSC101']);
    $this->actingAs($this->student)->get(route('student.courses.registration'))->assertOk()->assertSee('Registration checklist');
    expect(CourseRegistration::count())->toBe(0);
    $other->prerequisites()->detach();
    ClassSchedule::create($this->slot);
    ClassSchedule::create(array_replace($this->slot,['subject'=>$other->id]));
    $this->post(route('student.courses.register'),['semester'=>'First','course_ids'=>[$this->course->id,$other->id]])->assertSessionHasErrors();
    expect(CourseRegistration::count())->toBe(0);
});

it('summarizes attendance accurately and keeps student and lecturer records private', function () {
    $schedule = ClassSchedule::create($this->slot);
    foreach (['present','late','absent','excused'] as $i=>$status) {
        $meeting = AttendanceSession::create(['class_schedule_id'=>$schedule->id,'attendance_date'=>today()->subDays($i+1),'taken_by'=>$this->lecturer->id]);
        AttendanceRecord::create(['attendance_session_id'=>$meeting->id,'student_id'=>$this->student->id,'status'=>$status]);
    }
    $this->actingAs($this->student)->get(route('academic.assistance'))->assertOk()->assertSee('66.7%');
    $outsider = User::factory()->create(['usertype'=>'student']);
    $this->actingAs($outsider)->get(route('academic.assistance'))->assertOk()->assertDontSee('66.7%')->assertDontSee($this->student->name);
    $otherLecturer = User::factory()->create(['usertype'=>'lecturer']);
    $this->actingAs($otherLecturer)->get(route('academic.assistance'))->assertOk()->assertDontSee('66.7%');
    $this->actingAs($this->lecturer)->get(route('academic.assistance'))->assertOk()->assertSee('66.7%');
    $this->get(route('academic.assistance',['from'=>today()->toDateString(),'to'=>today()->toDateString()]))->assertOk()->assertDontSee('66.7%');
});

it('shows stale review reminders only to authorized staff and removes progressed results', function () {
    $result = Result::create(['user_id'=>$this->student->id,'uploaded_by'=>$this->lecturer->id,'matric_number'=>'AUTO001','session'=>$this->session->name,'semester'=>'First','level'=>'100','course_code'=>'REM101','course_title'=>'Review test','credit_unit'=>3,'ca_score'=>20,'exam_score'=>50,'department_id'=>$this->department->id]);
    DB::table('results')->where('id',$result->id)->update(['workflow_status'=>'submitted','updated_at'=>now()->subDays(4)]);
    $this->actingAs($this->admin)->get(route('academic.assistance'))->assertOk()->assertSee('REM101')->assertSee('Review');
    $this->actingAs($this->student)->get(route('academic.assistance'))->assertOk()->assertDontSee('REM101');
    $this->actingAs($this->lecturer)->get(route('academic.assistance'))->assertOk()->assertDontSee('REM101');
    DB::table('results')->where('id',$result->id)->update(['workflow_status'=>'published']);
    $this->actingAs($this->admin)->get(route('academic.assistance'))->assertOk()->assertDontSee('REM101');
    $bursar = User::factory()->create(['usertype'=>'bursar']);
    $this->actingAs($bursar)->get(route('academic.assistance'))->assertForbidden();
});

it('detects each shared timetable resource independently', function (string $resource) {
    ClassSchedule::create($this->slot);
    $faculty = Faculty::first();
    $department = Department::create(['name'=>'Other department','faculty_id'=>$faculty->id]);
    $lecturer = User::factory()->create(['usertype'=>'lecturer']);
    $course = $this->course->replicate(); $course->code='OTH101';
    $course->department_id = $resource === 'cohort' ? $this->department->id : $department->id;
    $course->save();
    $data = array_replace($this->slot,['subject'=>$course->id,'department_id'=>$course->department_id,
        'lecturer_id'=>$resource === 'lecturer' ? $this->lecturer->id : $lecturer->id,
        'room'=>$resource === 'room' ? 'hall a' : 'Hall B']);
    $this->actingAs($this->admin)->post(route('admin.class-schedules.store'),$data)->assertSessionHasErrors('start_time');
})->with(['room','lecturer','cohort']);

it('blocks conflicting registrations through the admin endpoint too', function () {
    $other = $this->course->replicate(); $other->code='CSC102'; $other->save();
    ClassSchedule::create($this->slot);
    ClassSchedule::create(array_replace($this->slot,['subject'=>$other->id]));
    $this->actingAs($this->admin)->put(route('admin.course-registrations.update',$this->student),
        ['session'=>$this->session->name,'semester'=>'First','course_ids'=>[$this->course->id,$other->id]])->assertSessionHasErrors('course_ids');
    expect(CourseRegistration::count())->toBe(0);
});

it('shows only the current student cohort and registered carryover timetable', function () {
    $current = ClassSchedule::create($this->slot);
    $other = $this->course->replicate(); $other->code='CSC201'; $other->level='200'; $other->save();
    $extra = ClassSchedule::create(array_replace($this->slot,['subject'=>$other->id,'level'=>'200']));
    expect(ClassSchedule::forStudent($this->student)->pluck('id')->all())->toBe([$current->id]);
    CourseRegistration::create(['user_id'=>$this->student->id,'course_id'=>$other->id,'semester'=>'First','session'=>$this->session->name,'status'=>'registered','registration_date'=>now()]);
    expect(ClassSchedule::forStudent($this->student)->count())->toBe(2);
    $old = AcademicSession::create(['name'=>'2025/2026','start_year'=>2025,'end_year'=>2026]);
    $other->update(['academic_session_id'=>$old->id]);
    expect(ClassSchedule::forStudent($this->student)->pluck('id')->all())->toBe([$current->id]);
});

it('excludes future and open scan meetings and handles entirely excused attendance', function () {
    $schedule = ClassSchedule::create($this->slot);
    foreach ([['attendance_date'=>today()->subDay(),'status'=>'excused'],['attendance_date'=>today()->addDay(),'status'=>'absent'],['attendance_date'=>today(),'status'=>'absent','scan_expires_at'=>now()->addHour()]] as $data) {
        $status = $data['status']; unset($data['status']);
        $meeting = AttendanceSession::create($data+['class_schedule_id'=>$schedule->id,'taken_by'=>$this->lecturer->id]);
        AttendanceRecord::create(['attendance_session_id'=>$meeting->id,'student_id'=>$this->student->id,'status'=>$status]);
    }
    $this->actingAs($this->student)->get(route('academic.assistance'))->assertOk()->assertViewHas('attendance',fn($rows)=>$rows->first()->total === 1)->assertSee('Not applicable');
});
