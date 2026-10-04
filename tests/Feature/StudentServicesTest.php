<?php

use App\Models\{AcademicSession, ActivityLog, CourseRegistration, Courses, Department, Faculty, GradingPolicy, Result, ResultAppeal, TranscriptDocument, TranscriptRequest, User};
use App\Services\StudentServices;
use App\Support\StudentUpdateFeed;
use Illuminate\Support\Facades\{DB, Mail};

beforeEach(function () {
    Mail::fake();
    $this->session = AcademicSession::create(['name'=>'2026/2027','start_year'=>2026,'end_year'=>2027,'is_active'=>true]);
    $faculty = Faculty::create(['name'=>'Science','code'=>'SCI']);
    $this->department = Department::create(['name'=>'Computing','faculty_id'=>$faculty->id]);
    $this->student = User::factory()->create(['usertype'=>'student','department_id'=>$this->department->id,'level'=>'100','entry_year'=>2026,'matric_number'=>'SERV001']);
    $this->other = User::factory()->create(['usertype'=>'student','department_id'=>$this->department->id,'level'=>'100','entry_year'=>2026]);
    $this->admin = User::factory()->create(['usertype'=>'admin']);
});

function serviceAppeal($test, array $extra = []): ResultAppeal
{
    return ResultAppeal::create($extra + ['user_id'=>$test->student->id,'department_id'=>$test->department->id,'session'=>$test->session->name,'semester'=>'First','course_code'=>'SERV101','message'=>'Please investigate the missing result.','status'=>'awaiting_payment']);
}

it('renders an automatic onboarding checklist without inventing application obligations', function () {
    $this->actingAs($this->student)->get(route('student.services'))->assertOk()->assertSee('Your onboarding checklist')->assertSee('Graduation requirements are not fully configured')->assertDontSee('Application payment');
    $steps = collect(StudentServices::onboarding($this->student));
    expect($steps->firstWhere('label','Confirm enrollment')['done'])->toBeTrue()
        ->and($steps->firstWhere('label','First semester registration started')['done'])->toBeFalse();
    $this->student->update(['entry_year'=>null,'matric_number'=>null]);
    $this->get(route('student.services'))->assertOk()->assertSee('Missing or invalid entry year')->assertSee('assign your matriculation number');
    $this->actingAs($this->admin)->get(route('student.services'))->assertForbidden();
});

it('tracks appeal changes and responses without duplicate events for unchanged saves', function () {
    $appeal = serviceAppeal($this);
    $appeal->update(['status'=>'open']);
    $this->actingAs($this->admin)->post(route('academic.appeals.resolve',$appeal),['status'=>'in_review','response'=>'Your submitted evidence is being reviewed.'])->assertSessionHasNoErrors();
    $appeal->refresh()->save();
    expect(ActivityLog::where('action','student_service_appeal')->count())->toBe(3);
    $this->actingAs($this->student)->get(route('student.services'))->assertOk()->assertSee('Your submitted evidence is being reviewed.')->assertSee('The academic office is reviewing your appeal.');
    $this->actingAs($this->other)->get(route('student.services'))->assertOk()->assertDontSee('SERV101')->assertDontSee('Your submitted evidence is being reviewed.');
    expect(StudentUpdateFeed::forUser($this->other)->where('title','Appeal Update'))->toHaveCount(0);
    expect(StudentUpdateFeed::forUser($this->student)->where('title','Appeal Update'))->toHaveCount(3);
});

it('announces transcript decisions and revocations only to the document owner', function () {
    $request = TranscriptRequest::create(['user_id'=>$this->student->id,'department_id'=>$this->department->id,'purpose'=>'Official transcript for postgraduate admission','status'=>'pending']);
    $request->update(['status'=>'issued']);
    $document = TranscriptDocument::create(['transcript_request_id'=>$request->id,'user_id'=>$this->student->id,'department_id'=>$this->department->id,'verification_code'=>(string)Illuminate\Support\Str::uuid(),'path'=>'documents/transcripts/services-test.pdf','version'=>1,'official'=>true,'status'=>'valid','result_versions'=>[]]);
    $this->actingAs($this->student)->get(route('student.services'))->assertOk()->assertSee('Download PDF');
    $this->actingAs($this->admin)->post(route('academic.transcripts.revoke',$document),['reason'=>'Source result was corrected after issuance.'])->assertSessionHasNoErrors();
    $this->actingAs($this->student)->get(route('student.services'))->assertOk()->assertSee('Source result was corrected after issuance.')->assertDontSee('Download PDF');
    expect(StudentUpdateFeed::forUser($this->student)->where('title','Transcript Revoked'))->toHaveCount(1);
    expect(StudentUpdateFeed::forUser($this->other)->where('title','Transcript Revoked'))->toHaveCount(0);
});

it('rolls back service events when the academic transaction fails', function () {
    $appeal = serviceAppeal($this);
    try {
        DB::transaction(function () use ($appeal) { $appeal->update(['status'=>'open']); throw new RuntimeException('rollback'); });
    } catch (RuntimeException $e) {}
    expect($appeal->fresh()->status)->toBe('awaiting_payment')->and(ActivityLog::where('action','student_service_appeal')->count())->toBe(1);
});

it('bases graduation preparation on configured published results and identifies missing records', function () {
    GradingPolicy::create(GradingPolicy::defaults()+['department_id'=>$this->department->id,'session'=>$this->session->name,'version'=>1]);
    GradingPolicy::first()->update(['graduation_credits'=>3,'graduation_cgpa'=>2,'required_courses'=>['SERV101']]);
    $course = Courses::create(['code'=>'SERV101','title'=>'Computing','credit_unit'=>3,'department_id'=>$this->department->id,'semester'=>'First','level'=>'100','academic_session_id'=>$this->session->id]);
    $registration = CourseRegistration::create(['user_id'=>$this->student->id,'course_id'=>$course->id,'status'=>'registered','semester'=>'First','session'=>$this->session->name,'registration_date'=>now()]);
    $result = Result::create(['user_id'=>$this->student->id,'uploaded_by'=>$this->admin->id,'course_registration_id'=>$registration->id,'matric_number'=>'SERV001','session'=>$this->session->name,'semester'=>'First','level'=>'100','course_code'=>'SERV101','course_title'=>'Computing','credit_unit'=>3,'ca_score'=>20,'exam_score'=>50,'department_id'=>$this->department->id]);
    expect(StudentServices::graduation($this->student)['eligible'])->toBeFalse();
    $this->actingAs($this->student)->get(route('student.services'))->assertOk()->assertSee('Registered courses without a published result')->assertDontSee('Recorded academic requirements met');
    DB::table('results')->where('id',$result->id)->update(['workflow_status'=>'published']);
    expect(StudentServices::graduation($this->student)['eligible'])->toBeTrue();
    $this->get(route('student.services'))->assertOk()->assertSee('Recorded academic requirements met');
});

it('handles missing session and department without assuming graduation eligibility', function () {
    $this->session->update(['is_active'=>false]);
    $this->student->update(['department_id'=>null]);
    $this->actingAs($this->student)->get(route('student.services'))->assertOk()->assertSee('activate a session')->assertSee('Missing department')->assertDontSee('Recorded academic requirements met');
});

it('records transcript rejection responses through the authorized workflow', function () {
    $request = TranscriptRequest::create(['user_id'=>$this->student->id,'department_id'=>$this->department->id,'purpose'=>'Transcript for admission review','status'=>'pending']);
    $this->actingAs($this->admin)->post(route('academic.transcripts.decide',$request),['decision'=>'reject','reason'=>'Please resolve the incomplete academic record.'])->assertSessionHasNoErrors();
    $this->actingAs($this->student)->get(route('student.services'))->assertOk()->assertSee('Please resolve the incomplete academic record.');
    expect(ActivityLog::where('action','student_service_transcript')->latest('id')->first()->properties['status'])->toBe('rejected');
    Mail::assertNothingSent();
});
