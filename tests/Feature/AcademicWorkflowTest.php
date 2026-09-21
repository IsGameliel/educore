<?php

use App\Models\AcademicSession;
use App\Models\CourseRegistration;
use App\Models\Courses;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\GradingPolicy;
use App\Models\Result;
use App\Models\ResultAppeal;
use App\Models\ResultCorrection;
use App\Models\ResultRevision;
use App\Models\TranscriptDocument;
use App\Models\TranscriptRequest;
use App\Models\User;
use App\Services\Academic\AcademicStanding;
use App\Services\Academic\Grading;
use App\Services\Academic\ResultWorkflow;
use App\Services\Academic\Transcripts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

function academicFixture(): array
{
    $session = AcademicSession::create(['name' => '2025/2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id, 'pass_mark' => 40]);
    $student = User::factory()->create(['usertype' => 'student', 'department_id' => $department->id, 'matric_number' => 'TEST001', 'level' => '100']);
    $lecturer = User::factory()->create(['usertype' => 'lecturer', 'department_id' => $department->id]);
    $admin = User::factory()->create(['usertype' => 'admin']);
    $officer = User::factory()->create(['usertype' => 'exam_officer']);
    $course = Courses::create(['code' => 'CSC101', 'title' => 'Computing', 'credit_unit' => 3, 'department_id' => $department->id, 'semester' => 'First', 'level' => '100', 'academic_session_id' => $session->id]);
    $lecturer->assignedCourses()->attach($course->id);
    $registration = CourseRegistration::create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'registered', 'semester' => 'First', 'session' => $session->name, 'registration_date' => now()]);
    $result = Result::create(['course_registration_id' => $registration->id, 'user_id' => $student->id, 'uploaded_by' => $lecturer->id, 'matric_number' => 'TEST001', 'session' => $session->name, 'semester' => 'First', 'level' => '100', 'course_code' => $course->code, 'course_title' => $course->title, 'credit_unit' => 3, 'ca_score' => 20, 'exam_score' => 50, 'department_id' => $department->id]);

    return compact('session', 'department', 'student', 'lecturer', 'admin', 'officer', 'course', 'result');
}

function publishAcademicFixture(array $f): void
{
    ResultWorkflow::transition($f['result'], $f['lecturer'], 'submit', 'Marks checked and submitted');
    foreach (['review', 'approve', 'publish'] as $action) {
        ResultWorkflow::transition($f['result'], $f['admin'], $action, 'Academic review completed');
    }
    $f['result']->refresh();
}

it('keeps drafts private and permits only admin or exam officer release through every stage', function () {
    $f = academicFixture();
    $this->actingAs($f['student'])->get(route('academic.index'))->assertOk()->assertDontSee('CSC101');
    $this->get(route('academic.show', $f['result']))->assertNotFound();
    $this->actingAs($f['lecturer'])->post(route('academic.transition', $f['result']), ['action' => 'submit', 'reason' => 'Complete submission'])->assertRedirect();
    $this->post(route('academic.transition', $f['result']), ['action' => 'review', 'reason' => 'Reviewed all marks'])->assertForbidden();
    $this->actingAs($f['officer'])->post(route('academic.transition', $f['result']), ['action' => 'publish', 'reason' => 'Publish this now'])->assertSessionHasErrors('action');
    foreach (['review', 'approve', 'publish'] as $action) {
        $this->post(route('academic.transition', $f['result']), ['action' => $action, 'reason' => 'All checks complete'])->assertSessionHasNoErrors()->assertRedirect();
    }
    $this->actingAs($f['student'])->get(route('academic.index'))->assertOk()->assertSee('CSC101');
    $this->get(route('student.results.show.bySemester', [$f['student']->id, 'First', 'session' => '2025/2026']))->assertOk()->assertSee('70');
});

it('locks submitted and published marks and records approved corrections as a new version', function () {
    $f = academicFixture();
    publishAcademicFixture($f);
    $this->actingAs($f['admin']);
    expect(fn () => $f['result']->update(['score' => 10]))->toThrow(ValidationException::class);
    $result = $f['result']->fresh();
    $version = $result->version;
    ResultWorkflow::requestCorrection($result, $f['lecturer'], ['score' => 60, 'ca_score' => null, 'exam_score' => null, 'outcome_status' => 'graded', 'credit_unit' => 3, 'attempt_type' => 'regular'], 'Exam mark was entered incorrectly');
    $correction = ResultCorrection::firstOrFail();
    $this->actingAs($f['officer'])->post(route('academic.correction.decide', $correction), ['decision' => 'approved', 'reason' => 'Checked the marked script'])->assertSessionHasNoErrors();
    expect($result->fresh()->score)->toEqual(60)->and($result->fresh()->version)->toBe($version + 1)->and($result->fresh()->workflow_status)->toBe('approved');
    $revision = ResultRevision::where('action', 'correction_approved')->firstOrFail();
    expect($revision->before['score'])->toEqual(70)->and($revision->after['score'])->toEqual(60)->and($revision->approver_id)->toBe($f['officer']->id);
});

it('rejects exam officer self approval and an unauthorized lecturer course', function () {
    $f = academicFixture();
    $other = User::factory()->create(['usertype' => 'lecturer']);
    $this->actingAs($other)->get(route('academic.show', $f['result']))->assertForbidden();
    $this->actingAs($f['officer'])->post(route('academic.transition', $f['result']), ['action' => 'submit', 'reason' => 'Submit checked marks'])->assertRedirect();
    $this->post(route('academic.transition', $f['result']), ['action' => 'review', 'reason' => 'Self review attempted'])->assertSessionHasErrors('action');
});

it('blocks approval when registered students have missing results and detects duplicate or unregistered results', function () {
    $f = academicFixture();
    $student = User::factory()->create(['usertype' => 'student', 'department_id' => $f['department']->id]);
    CourseRegistration::create(['user_id' => $student->id, 'course_id' => $f['course']->id, 'session' => $f['session']->name, 'semester' => 'First', 'status' => 'registered', 'registration_date' => now()]);
    ResultWorkflow::transition($f['result'], $f['lecturer'], 'submit', 'Submit marks');
    ResultWorkflow::transition($f['result'], $f['admin'], 'review', 'Review marks');
    expect(fn () => ResultWorkflow::transition($f['result'], $f['admin'], 'approve', 'Approve marks'))->toThrow(ValidationException::class);
    expect(implode(' ', ResultWorkflow::problems($f['result'])))->toContain('Missing result');
    DB::table('course_registrations')->update(['status' => 'withdrawn']);
    expect(implode(' ', ResultWorkflow::problems($f['result'])))->toContain('not registered');
});

it('keeps historical grades when pass marks change and validates assessment components consistently', function () {
    $f = academicFixture();
    $this->actingAs($f['admin'])->post(route('admin.departments.passmarks.update'), ['pass_marks' => [$f['department']->id => 80]])->assertRedirect();
    expect($f['result']->fresh()->grade)->toBe('A')->and($f['result']->fresh()->policy_snapshot['pass_mark'])->toBe(40);
    foreach ([['ca_score' => -5, 'exam_score' => 60], ['ca_score' => 40, 'exam_score' => 30], ['ca_score' => 10]] as $values) {
        expect(fn () => Grading::calculate($values, GradingPolicy::defaults()))->toThrow(ValidationException::class);
    }
    expect(Grading::calculate(['outcome_status' => 'absent', 'score' => 0], GradingPolicy::defaults())['score'])->toBeNull();
});

it('flags ten outstanding failures as repeat and nine as carryover', function () {
    $f = academicFixture();
    for ($i = 1; $i <= 10; $i++) {
        $r = Result::create(array_merge($f['result']->only(['user_id', 'matric_number', 'session', 'semester', 'level', 'course_title', 'credit_unit', 'department_id']), ['course_code' => 'FAIL'.$i, 'score' => 25]));
        $r->workflow_status = 'published';
        $r->saveQuietly();
    }
    $report = AcademicStanding::report($f['student'], $f['department']->id, $f['session']->name);
    expect($report['standing'])->toBe('Repeat')->and($report['failedThisSession'])->toBe(10);
    DB::table('results')->where('course_code', 'FAIL10')->update(['grade' => 'A', 'grade_point' => 5, 'score' => 75]);
    expect(AcademicStanding::report($f['student'], $f['department']->id, $f['session']->name)['standing'])->toBe('Carryover');
});

it('counts repeat attempts according to policy and excludes exceptional results from GPA', function () {
    $rows = collect([
        new Result(['course_code' => 'CSC101', 'session' => '2024/2025', 'semester' => 'First', 'credit_unit' => 3, 'outcome_status' => 'graded', 'grade_point' => 0]),
        new Result(['course_code' => 'CSC101', 'session' => '2025/2026', 'semester' => 'First', 'credit_unit' => 3, 'outcome_status' => 'graded', 'grade_point' => 4]),
        new Result(['course_code' => 'CSC102', 'credit_unit' => 9, 'outcome_status' => 'absent', 'grade_point' => null]),
    ]);
    expect(AcademicStanding::gpa($rows, 'all'))->toBe(2.0)->and(AcademicStanding::gpa($rows, 'latest'))->toBe(4.0)->and(AcademicStanding::gpa($rows, 'highest'))->toBe(4.0);
});

it('protects private transcripts and revokes them after correction', function () {
    Storage::fake('local');
    Storage::fake('public');
    $f = academicFixture();
    publishAcademicFixture($f);
    $request = TranscriptRequest::create(['user_id' => $f['student']->id, 'department_id' => $f['department']->id, 'purpose' => 'Graduate application']);
    $document = Transcripts::issue($f['student'], $f['department']->id, $f['admin'], null, null, $request);
    Storage::disk('local')->assertExists($document->path);
    Storage::disk('public')->assertMissing($document->path);
    $other = User::factory()->create(['usertype' => 'student']);
    $this->actingAs($other)->get(route('documents.transcripts.show', basename($document->path)))->assertForbidden();
    $this->post(route('student.results.transcript.bySemester', [$f['student']->id, 'First', 'session' => $f['session']->name]))->assertForbidden();
    $this->actingAs($f['student'])->get(route('documents.transcripts.show', basename($document->path)))->assertOk();
    $this->get(route('academic.verify', $document->verification_code))->assertOk()->assertDontSee($f['student']->name)->assertSee('Valid official transcript');
    $this->actingAs($f['admin']);
    ResultWorkflow::requestCorrection($f['result'], $f['lecturer'], ['score' => 60, 'ca_score' => null, 'exam_score' => null], 'Correction of total mark');
    ResultWorkflow::decideCorrection(ResultCorrection::firstOrFail(), $f['officer'], 'approved', 'Verified script');
    expect($document->fresh()->status)->toBe('revoked');
    $this->get(route('documents.transcripts.show', basename($document->path)))->assertStatus(410);
});

it('allows missing result appeals but protects another student evidence', function () {
    Storage::fake('local');
    $f = academicFixture();
    $this->actingAs($f['student'])->post(route('academic.appeals.store'), ['session' => $f['session']->name, 'semester' => 'First', 'course_code' => 'MISSING101', 'message' => 'My examination mark is missing from the portal.'])->assertSessionHasNoErrors();
    $appeal = ResultAppeal::firstOrFail();
    $other = User::factory()->create(['usertype' => 'student']);
    $this->actingAs($other)->get(route('academic.appeals.evidence', $appeal))->assertForbidden();
    $this->actingAs($f['officer'])->post(route('academic.appeals.resolve', $appeal), ['status' => 'in_review', 'response' => 'We are checking the examination register.'])->assertSessionHasNoErrors();
    $this->actingAs($f['student'])->get(route('academic.appeals'))->assertOk()->assertSee('checking the examination register');
});

it('renders the staff management screens and exam officer result entry', function () {
    $f = academicFixture();
    foreach (['admin', 'officer'] as $role) {
        $this->actingAs($f[$role]);
        foreach (['academic.index', 'academic.policies', 'academic.appeals', 'academic.transcripts', 'academic.entry', 'academic.upload'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('academic.show', $f['result']))->assertOk();
        $this->get(route('academic.reports', ['department_id' => $f['department']->id, 'session' => $f['session']->name]))->assertOk();
    }
});

it('adopts a revised pass mark only through an approved correction for published results', function () {
    $f = academicFixture();
    $f['result']->update(['ca_score' => 15, 'exam_score' => 30]);
    publishAcademicFixture($f);
    $this->actingAs($f['admin'])->post(route('academic.policies.store'), [
        'department_id' => $f['department']->id, 'session' => $f['session']->name, 'ca_max' => 30, 'exam_max' => 70, 'pass_mark' => 50,
        'bands' => GradingPolicy::defaults()['bands'], 'repeat_rule' => 'all', 'graduation_credits' => 120, 'graduation_cgpa' => 1.5, 'required_courses_text' => 'CSC101',
    ])->assertSessionHasNoErrors();
    expect($f['result']->fresh()->grade)->toBe('D');
    $this->post(route('academic.correction', $f['result']), ['ca_score' => 15, 'exam_score' => 30, 'score' => 45, 'credit_unit' => 3, 'outcome_status' => 'graded', 'attempt_type' => 'regular', 'reason' => 'Apply approved policy change', 'adopt_policy' => 1])->assertSessionHasNoErrors();
    $correction = ResultCorrection::firstOrFail();
    $this->post(route('academic.correction.decide', $correction), ['decision' => 'approved', 'reason' => 'Approve own proposed change'])->assertForbidden();
    $this->actingAs($f['officer'])->post(route('academic.correction.decide', $correction), ['decision' => 'approved', 'reason' => 'Policy change verified'])->assertSessionHasNoErrors();
    expect($f['result']->fresh()->grade)->toBe('F')->and($f['result']->fresh()->policy_snapshot['pass_mark'])->toBe(50);
    expect(ResultRevision::where('action', 'correction_approved')->first()->before['grade'])->toBe('D');
});

it('rolls back batch transitions instead of publishing only some selected records', function () {
    $f = academicFixture();
    $this->actingAs($f['admin']);
    $other = Result::create($f['result']->only(['user_id', 'matric_number', 'session', 'semester', 'level', 'course_title', 'credit_unit', 'department_id']) + ['course_code' => 'OTHER101', 'score' => 50]);
    ResultWorkflow::transition($other, $f['admin'], 'submit', 'Submit second result');
    $this->post(route('academic.batch'), ['result_ids' => [$f['result']->id, $other->id], 'action' => 'submit', 'reason' => 'Submit selected results'])->assertSessionHasErrors('action');
    expect($f['result']->fresh()->workflow_status)->toBe('draft');
});

it('creates a separate resit attempt without overwriting the original failure', function () {
    $f = academicFixture();
    $f['result']->update(['ca_score' => 10, 'exam_score' => 10]);
    publishAcademicFixture($f);
    $this->actingAs($f['officer'])->post(route('academic.resit', $f['result']), ['reason' => 'Approved supplementary examination'])->assertSessionHasNoErrors();
    $resit = Result::where('attempt_type', 'resit')->firstOrFail();
    expect($resit->attempt_number)->toBe(2)->and($resit->outcome_status)->toBe('not_submitted')->and($f['result']->fresh()->grade)->toBe('F');
    $this->put(route('academic.update', $resit), ['outcome_status' => 'graded', 'attempt_type' => 'resit', 'credit_unit' => 3, 'ca_score' => 20, 'exam_score' => 30, 'reason' => 'Supplementary examination marks'])->assertSessionHasNoErrors();
    expect($resit->fresh()->grade)->toBe('C')->and(ResultWorkflow::problems($resit->fresh()))->toBe([]);
});

it('excludes future results from historical CGPA and blocks incomplete graduation records', function () {
    $f = academicFixture();
    publishAcademicFixture($f);
    $later = Result::create($f['result']->only(['user_id', 'matric_number', 'level', 'course_title', 'credit_unit', 'department_id']) + ['session' => '2026/2027', 'semester' => 'Second', 'course_code' => 'CSC201', 'score' => 0]);
    $later->workflow_status = 'published';
    $later->saveQuietly();
    $report = AcademicStanding::report($f['student'], $f['department']->id, '2025/2026');
    expect($report['cgpa'])->toBe(5.0)->and($report['results']->count())->toBe(1)->and($report['configured'])->toBeFalse()->and($report['eligible'])->toBeFalse();
});

it('archives legacy public PDFs and snapshots scores without regrading them', function () {
    Storage::fake('public');
    Storage::fake('local');
    $f = academicFixture();
    DB::table('results')->where('id', $f['result']->id)->update(['policy_snapshot' => null, 'grade' => 'LEG', 'grade_point' => 2, 'transcript_path' => '/storage/documents/transcripts/old.pdf']);
    Storage::disk('public')->put('documents/transcripts/old.pdf', 'legacy bytes');
    $this->artisan('academic:prepare', ['--dry-run' => true])->assertSuccessful();
    Storage::disk('public')->assertExists('documents/transcripts/old.pdf');
    $this->artisan('academic:prepare')->assertSuccessful();
    Storage::disk('public')->assertMissing('documents/transcripts/old.pdf');
    Storage::disk('local')->assertExists('legacy-transcripts/old.pdf');
    expect($f['result']->fresh()->grade)->toBe('LEG')->and($f['result']->fresh()->transcript_path)->toBeNull()->and($f['result']->fresh()->policy_snapshot['legacy_baseline'])->toBeTrue();
    $this->artisan('academic:prepare')->assertSuccessful();
    expect(ResultRevision::where('action', 'legacy_baseline')->count())->toBe(1);
});

it('rejects official issuance while registered course marks are missing', function () {
    Storage::fake('local');
    $f = academicFixture();
    publishAcademicFixture($f);
    $course = Courses::create(['code' => 'MISSING101', 'title' => 'Missing marks', 'credit_unit' => 3, 'department_id' => $f['department']->id, 'semester' => 'Second', 'level' => '100', 'academic_session_id' => $f['session']->id]);
    CourseRegistration::create(['user_id' => $f['student']->id, 'course_id' => $course->id, 'session' => $f['session']->name, 'semester' => 'Second', 'status' => 'registered', 'registration_date' => now()]);
    $request = TranscriptRequest::create(['user_id' => $f['student']->id, 'department_id' => $f['department']->id, 'purpose' => 'Official application']);
    $this->actingAs($f['officer'])->post(route('academic.transcripts.decide', $request), ['decision' => 'issue', 'reason' => 'Ready for issuance'])->assertSessionHasErrors('transcript');
    expect(TranscriptDocument::count())->toBe(0)->and($request->fresh()->status)->toBe('pending');
});

it('retains the previous marks when a freshly created model is edited again', function () {
    $f = academicFixture();
    $f['result']->update(['ca_score' => 10, 'exam_score' => 40]);
    $revision = ResultRevision::where('result_id', $f['result']->id)->latest('id')->firstOrFail();
    expect($revision->action)->toBe('draft_updated')
        ->and($revision->before['score'])->toEqual(70)
        ->and($revision->after['score'])->toEqual(50);
});

it('requires an admin or exam officer to authorize a resit and rejects passed exams', function () {
    $f = academicFixture();
    publishAcademicFixture($f);
    $this->actingAs($f['lecturer'])->post(route('academic.resit', $f['result']), ['reason' => 'Requested resit exam'])->assertForbidden();
    $this->actingAs($f['student'])->post(route('academic.resit', $f['result']), ['reason' => 'Requested resit exam'])->assertForbidden();
    $this->actingAs($f['officer'])->post(route('academic.resit', $f['result']), ['reason' => 'Requested resit exam'])->assertSessionHasErrors('resit');
    expect(Result::where('attempt_type', 'resit')->count())->toBe(0);
});

it('keeps both exam scores and clears the failure only after the resit is published', function () {
    $f = academicFixture();
    $f['result']->update(['ca_score' => 10, 'exam_score' => 10]);
    publishAcademicFixture($f);
    $original = $f['result']->fresh()->only(['score', 'grade', 'grade_point', 'version', 'workflow_status']);
    $this->actingAs($f['officer'])->post(route('academic.resit', $f['result']), ['reason' => 'Student approved for resit examination'])->assertSessionHasNoErrors();
    $resit = Result::where('attempt_type', 'resit')->sole();
    expect($resit->course_registration_id)->toBe($f['result']->course_registration_id)
        ->and($resit->resit_of_result_id)->toBe($f['result']->id)
        ->and($resit->resit_authorized_by)->toBe($f['officer']->id)
        ->and($resit->resit_authorized_at)->not->toBeNull();
    $authorization = ResultRevision::where('result_id', $resit->id)->where('action', 'resit_authorized')->sole();
    expect($authorization->reason)->toBe('Student approved for resit examination');
    $this->post(route('academic.resit', $f['result']), ['reason' => 'Duplicate authorization'])->assertSessionHasErrors('result');
    expect(Result::where('attempt_type', 'resit')->count())->toBe(1);

    $this->actingAs($f['student'])->get(route('academic.show', $f['result']))->assertOk()->assertDontSee('Resit exam');
    $this->get(route('academic.show', $resit))->assertNotFound();
    $this->actingAs($f['lecturer'])->put(route('academic.update', $resit), [
        'outcome_status' => 'graded', 'attempt_type' => 'resit', 'credit_unit' => 3,
        'ca_score' => 20, 'exam_score' => 40, 'reason' => 'Entered marked resit examination',
    ])->assertSessionHasNoErrors();
    expect(AcademicStanding::report($f['student'], $f['department']->id, $f['session']->name)['failedThisSession'])->toBe(1);
    $this->post(route('academic.transition', $resit), ['action' => 'submit', 'reason' => 'Resit marks checked'])->assertSessionHasNoErrors();
    $this->actingAs($f['admin']);
    foreach (['review', 'approve', 'publish'] as $action) {
        $this->post(route('academic.transition', $resit), ['action' => $action, 'reason' => 'Resit marks verified'])->assertSessionHasNoErrors();
    }
    expect($f['result']->fresh()->only(array_keys($original)))->toEqual($original)
        ->and($resit->fresh()->score)->toEqual(60)
        ->and(AcademicStanding::report($f['student'], $f['department']->id, $f['session']->name)['failedThisSession'])->toBe(0);
    $this->actingAs($f['student'])->get(route('academic.show', $resit))->assertOk()->assertSee('Original exam')->assertSee('Resit exam')->assertSee('20')->assertSee('60');
    $this->get(route('student.results.show.bySemester', [$f['student']->id, 'First', 'session' => $f['session']->name]))->assertOk()->assertSee('Resit exam')->assertSee('Regular exam');
});

it('does not let a resit overwrite another course or change into an original exam', function () {
    $f = academicFixture();
    $f['result']->update(['ca_score' => 10, 'exam_score' => 10]);
    publishAcademicFixture($f);
    $this->actingAs($f['officer'])->post(route('academic.resit', $f['result']), ['reason' => 'Authorize resit exam']);
    $resit = Result::where('attempt_type', 'resit')->sole();
    $this->put(route('academic.update', $resit), [
        'outcome_status' => 'graded', 'attempt_type' => 'regular', 'credit_unit' => 3,
        'score' => 60, 'reason' => 'Attempt to relabel resit',
    ])->assertSessionHasErrors('attempt_type');
    expect(fn () => $resit->update(['course_code' => 'ANOTHER101']))->toThrow(ValidationException::class);
    expect($resit->fresh()->attempt_type)->toBe('resit')->and($resit->fresh()->course_code)->toBe($f['result']->course_code);
});

it('links manual results to active registrations and rejects wrong semesters and unregistered students', function () {
    $f = academicFixture();
    $student = User::factory()->create(['usertype' => 'student', 'department_id' => $f['department']->id, 'matric_number' => 'LINK001', 'level' => '200']);
    $payload = ['user_id' => $student->id, 'course_id' => $f['course']->id, 'department_id' => $f['department']->id,
        'session' => $f['session']->name, 'semester' => 'First', 'score' => 65];
    $this->actingAs($f['admin'])->post(route('academic.results.store'), $payload)->assertSessionHasErrors('course_registration');
    $registration = CourseRegistration::create(['user_id' => $student->id, 'course_id' => $f['course']->id,
        'session' => $f['session']->name, 'semester' => 'First', 'status' => 'pending', 'registration_date' => now()]);
    $this->post(route('academic.results.store'), $payload)->assertSessionHasErrors('course_registration');
    $registration->update(['status' => 'approved']);
    $this->post(route('academic.results.store'), array_merge($payload, ['semester' => 'Second']))->assertSessionHasErrors('course_registration');
    $this->post(route('academic.results.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
    $result = Result::where('user_id', $student->id)->firstOrFail();
    expect($result->course_registration_id)->toBe($registration->id)->and($result->level)->toBe('100');
    $this->get(route('academic.entry', ['registration_id' => $registration->id]))->assertRedirect(route('academic.show', $result));
    expect(fn () => $result->update(['semester' => 'Second']))->toThrow(ValidationException::class);
    expect(fn () => $result->fresh()->update(['course_registration_id' => null]))->toThrow(ValidationException::class);
});

it('protects registrations with results from removal or withdrawal including hidden and deleted drafts', function () {
    $f = academicFixture();
    $registration = $f['result']->registration;
    $this->actingAs($f['admin'])->put(route('admin.course-registrations.update', $f['student']), [
        'session' => $f['session']->name, 'semester' => 'First', 'course_ids' => [],
    ])->assertSessionHasErrors('course_registration');
    expect($registration->fresh())->not->toBeNull();
    $this->actingAs($f['student'])->postJson(route('student.courses.withdraw'), ['course_id' => $f['course']->id, 'session' => $f['session']->name, 'semester' => 'First'])->assertUnprocessable()->assertJsonValidationErrors('course_registration');
    expect(fn () => $registration->update(['status' => 'withdrawn']))->toThrow(ValidationException::class);
    expect(fn () => $registration->delete())->toThrow(ValidationException::class);
    $this->actingAs($f['admin']);
    $f['result']->delete();
    expect(fn () => $registration->fresh()->delete())->toThrow(ValidationException::class);
});

it('shows registration results to staff but keeps student registration marks private until published', function () {
    $f = academicFixture();
    $registration = $f['result']->registration;
    $this->actingAs($f['admin'])->get(route('admin.course-registrations.show', [$f['student'], 'session' => $f['session']->name, 'semester' => 'First']))
        ->assertOk()->assertSee(route('academic.show', $f['result']), false)->assertSee('draft');
    $url = route('student.courses.registered', ['semester' => 'First', 'session' => $f['session']->name]);
    $this->actingAs($f['student'])->get($url)->assertOk()->assertSee('No published result')->assertDontSee(route('academic.show', $f['result']), false);
    $this->actingAs($f['admin']);
    publishAcademicFixture($f);
    $this->actingAs($f['student'])->get($url)->assertOk()->assertSee(route('academic.show', $f['result']), false);
});

it('generates a blank mark roster only for active students registered for that course and session', function () {
    $f = academicFixture();
    $excluded = User::factory()->create(['usertype' => 'student', 'matric_number' => 'PENDING001']);
    CourseRegistration::create(['user_id' => $excluded->id, 'course_id' => $f['course']->id, 'session' => $f['session']->name,
        'semester' => 'First', 'status' => 'pending', 'registration_date' => now()]);
    $rows = (new \App\Exports\ResultUploadTemplateSheet($f['course'], 'CSC101'))->array();
    expect($rows)->toHaveCount(9)->and($rows[8])->toBe([1, 'TEST001', $f['student']->name, null, null, null]);
});

it('rejects uploaded marks for a student without an active course registration', function () {
    $f = academicFixture();
    $student = User::factory()->create(['usertype' => 'student', 'department_id' => $f['department']->id, 'matric_number' => 'UNREGISTERED']);
    $rows = collect([['Course code', 'CSC101'], ['Session', '2025/2026'], ['Level', '100'], [], [], [], [],
        ['S/NO', 'MATRIC NO.', 'NAME', 'CA', 'EXAM', 'Total'], [1, 'UNREGISTERED', $student->name, 20, 40, 60]]);
    $import = new \App\Imports\ResultsImport($f['course'], $f['lecturer']->id, '2025/2026', 'First');
    expect(fn () => $import->importRows($rows))->toThrow(ValidationException::class);
    expect(Result::where('user_id', $student->id)->exists())->toBeFalse();
});

it('backfills historical registration links without altering scores versions or publication', function () {
    $f = academicFixture();
    publishAcademicFixture($f);
    $before = $f['result']->fresh();
    $migration = require database_path('migrations/2026_09_21_130000_link_results_to_course_registrations.php');
    DB::table('results')->update(['course_registration_id' => null]);
    $migration->backfill();
    $after = $f['result']->fresh();
    expect($after->course_registration_id)->toBe($before->course_registration_id)
        ->and($after->score)->toBe($before->score)->and($after->version)->toBe($before->version)
        ->and($after->workflow_status)->toBe('published');
});


it('opens registration entry only for authorized staff and filters the student selector by enrollment', function () {
    $f = academicFixture();
    $student = User::factory()->create(['usertype' => 'student', 'department_id' => $f['department']->id, 'matric_number' => 'ENTRY001']);
    $registration = CourseRegistration::create(['user_id' => $student->id, 'course_id' => $f['course']->id, 'session' => '2025/2026',
        'semester' => 'First', 'status' => 'registered', 'registration_date' => now()]);
    $this->actingAs($f['officer'])->get(route('academic.entry', ['registration_id' => $registration->id]))->assertOk()->assertSee('ENTRY001')->assertSee('Save draft result');
    $this->actingAs($student)->get(route('academic.entry', ['registration_id' => $registration->id]))->assertForbidden();
    $unassigned = User::factory()->create(['usertype' => 'lecturer']);
    $this->actingAs($unassigned)->get(route('lecturer.results.create', ['registration_id' => $registration->id]))->assertForbidden();
    $this->actingAs($f['lecturer'])->get(route('lecturer.results.create', ['registration_id' => $registration->id]))->assertOk();
    $this->actingAs($f['admin'])->getJson('/admin/results/get-students/'.$f['department']->id.'?'.http_build_query([
        'course_id' => $f['course']->id, 'session' => '2025/2026', 'semester' => 'First',
    ]))->assertOk()->assertJsonCount(2);
    $this->getJson('/admin/results/get-students/'.$f['department']->id.'?'.http_build_query([
        'course_id' => $f['course']->id, 'session' => '2024/2025', 'semester' => 'First',
    ]))->assertOk()->assertJsonCount(0);
});


it('allows an admin to submit review approve and publish their own result with audit records', function () {
    $f = academicFixture();
    $f['result']->update(['uploaded_by' => $f['admin']->id]);
    $this->actingAs($f['admin']);
    foreach (['submit', 'review', 'approve', 'publish'] as $action) {
        $this->post(route('academic.transition', $f['result']), ['action' => $action, 'reason' => 'Admin verified the result'])->assertSessionHasNoErrors()->assertRedirect();
    }
    expect($f['result']->fresh()->workflow_status)->toBe('published')
        ->and($f['result']->fresh()->approved_by)->toBe($f['admin']->id)
        ->and(ResultRevision::where('result_id', $f['result']->id)->where('action', 'approve')->first()->actor_id)->toBe($f['admin']->id);
});

it('blocks exam officer self approval after another manager reviews and allows another manager to approve', function () {
    $f = academicFixture();
    $f['result']->update(['uploaded_by' => $f['officer']->id]);
    $this->actingAs($f['officer'])->post(route('academic.transition', $f['result']), ['action' => 'submit', 'reason' => 'Checked marks submitted'])->assertSessionHasNoErrors();
    $this->get(route('academic.show', $f['result']))->assertOk()->assertSee('Another authorized staff member');
    $this->post(route('academic.batch'), ['result_ids' => [$f['result']->id], 'action' => 'review', 'reason' => 'Attempt own review'])->assertSessionHasErrors('action');
    $this->actingAs($f['admin'])->post(route('academic.transition', $f['result']), ['action' => 'review', 'reason' => 'Independent review complete'])->assertSessionHasNoErrors();
    $this->actingAs($f['officer'])->post(route('academic.transition', $f['result']), ['action' => 'approve', 'reason' => 'Attempt own approval'])->assertSessionHasErrors('action');
    $this->actingAs($f['admin'])->post(route('academic.transition', $f['result']), ['action' => 'approve', 'reason' => 'Independent approval complete'])->assertSessionHasNoErrors();
});

it('registers an outstanding lower level course in the new session and preserves its failed result', function () {
    $f = academicFixture();
    $f['result']->update(['ca_score' => 5, 'exam_score' => 10]);
    publishAcademicFixture($f);
    $f['session']->update(['is_active' => false]);
    $next = AcademicSession::create(['name' => '2026/2027', 'start_year' => 2026, 'end_year' => 2027, 'is_active' => true]);
    $f['student']->update(['level' => '200']);
    $offering = $f['course']->replicate();
    $offering->academic_session_id = $next->id;
    $offering->save();
    $this->actingAs($f['student'])->get(route('student.courses.registration'))->assertOk()->assertSee('Outstanding / Carryover Courses')->assertSee('CSC101');
    $this->actingAs($f['student'])->post(route('student.courses.register'), [
        'level' => '200', 'semester' => 'First', 'carryover_ids' => [$offering->id],
    ])->assertSessionHasNoErrors()->assertSessionHas('success');
    $registration = CourseRegistration::where('course_id', $offering->id)->where('user_id', $f['student']->id)->firstOrFail();
    expect($registration->previous_result_id)->toBe($f['result']->id)
        ->and($registration->session)->toBe('2026/2027')
        ->and($f['result']->fresh()->score)->toEqual(15);
    $this->post(route('student.courses.register'), [
        'semester' => 'First', 'carryover_ids' => [$offering->id],
    ])->assertSessionHasErrors('course_registration');
});

it('rejects carryover registration without an outstanding published failure', function () {
    $f = academicFixture();
    $this->actingAs($f['student'])->post(route('student.courses.register'), [
        'semester' => 'First', 'carryover_ids' => [$f['course']->id],
    ])->assertSessionHasErrors('course_registration');
    $this->actingAs($f['admin']);
    publishAcademicFixture($f);
    expect(\App\Services\Academic\CarryoverRegistration::available($f['student'], '2026/2027'))->toBeEmpty();
});

it('explains self correction decisions and lets another manager reject without changing marks', function () {
    $f = academicFixture();
    publishAcademicFixture($f);
    $result = $f['result']->fresh();
    ResultWorkflow::requestCorrection($result, $f['admin'], [
        'score' => 60, 'ca_score' => null, 'exam_score' => null, 'outcome_status' => 'graded',
        'credit_unit' => 3, 'attempt_type' => 'regular',
    ], 'Please check the original script');
    $correction = ResultCorrection::firstOrFail();
    $url = route('academic.correction.decide', $correction);
    $this->actingAs($f['admin'])->get(route('academic.show', $result))
        ->assertOk()->assertSee('Another admin or exam officer must approve or reject your correction request.')
        ->assertDontSee('action="'.$url.'"', false);
    foreach (['approved', 'rejected'] as $decision) {
        $this->from(route('academic.show', $result))->post($url, [
            'decision' => $decision, 'reason' => 'Checked the original script',
        ])->assertRedirect(route('academic.show', $result))->assertSessionHasErrors('correction');
        expect($correction->fresh()->status)->toBe('pending');
    }
    $this->actingAs($f['officer'])->post($url, [
        'decision' => 'rejected', 'reason' => 'Original marks are correct',
    ])->assertRedirect()->assertSessionHas('success', 'Correction rejected. The existing result and scores remain unchanged.');
    expect($correction->fresh()->status)->toBe('rejected')
        ->and($result->fresh()->score)->toEqual(70)
        ->and($result->fresh()->workflow_status)->toBe('published');
    expect(ResultRevision::where('result_id', $result->id)->where('action', 'correction_rejected')->exists())->toBeTrue();
});
