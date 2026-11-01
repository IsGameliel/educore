<?php

use App\Models\{AcademicSession, Courses, CourseRegistration, Department, Faculty, RegistrationSetting, Result, User};
use App\Imports\CourseImport;
use App\Exports\DepartmentalBroadsheetExport;

function courseStatusFixture(): array
{
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $session = AcademicSession::create(['name' => '2026/2027', 'start_year' => 2026, 'end_year' => 2027, 'is_active' => true]);
    $student = User::factory()->create(['usertype' => 'student', 'department_id' => $department->id, 'level' => '100']);
    RegistrationSetting::current()->update(['registration_open' => true, 'require_fee_clearance' => false, 'require_late_registration_fee' => false]);
    $courses = collect(Courses::STATUSES)->mapWithKeys(function ($status, $i) use ($department, $session) {
        return [$status => Courses::create(['code' => 'CSC10'.$i, 'title' => $status.' Course', 'credit_unit' => 3,
            'semester' => 'First', 'department_id' => $department->id, 'level' => '100',
            'academic_session_id' => $session->id, 'course_status' => $status])];
    });
    return compact('department', 'session', 'student', 'courses');
}

it('rejects omitted mandatory courses and leaves elective courses optional', function () {
    $f = courseStatusFixture();
    $this->actingAs($f['student'])->postJson(route('student.courses.register'), [
        'semester' => 'First', 'session' => $f['session']->name, 'course_ids' => [$f['courses']['Elective']->id],
    ])->assertStatus(422)->assertJsonFragment(['error' => 'Required and Compulsory courses must be registered: CSC101, CSC102.']);
    expect(CourseRegistration::count())->toBe(0);
    $this->post(route('student.courses.register'), [
        'semester' => 'First', 'session' => $f['session']->name,
        'course_ids' => [$f['courses']['Required']->id, $f['courses']['Compulsory']->id],
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect(CourseRegistration::count())->toBe(2);
    $this->post(route('student.courses.register'), [
        'semester' => 'First', 'session' => $f['session']->name, 'course_ids' => [$f['courses']['Elective']->id],
    ])->assertSessionHasNoErrors();
    expect(CourseRegistration::count())->toBe(3);
});

it('preselects mandatory courses and prevents student withdrawal', function () {
    $f = courseStatusFixture();
    $this->actingAs($f['student'])->get(route('student.courses.registration'))
        ->assertOk()->assertSee('Required and Compulsory courses are selected automatically')
        ->assertSee('data-mandatory="1"', false);
    $course = $f['courses']['Required'];
    CourseRegistration::create(['user_id' => $f['student']->id, 'course_id' => $course->id,
        'session' => $f['session']->name, 'semester' => 'First', 'status' => 'registered', 'registration_date' => now()]);
    $this->postJson(route('student.courses.withdraw'), ['course_id' => $course->id, 'session' => $f['session']->name, 'semester' => 'First'])
        ->assertUnprocessable()->assertJsonValidationErrors('course_registration');
    expect(CourseRegistration::count())->toBe(1);
});

it('imports status names and abbreviations and reports invalid statuses', function () {
    $f = courseStatusFixture();
    $import = new CourseImport;
    $rows = collect(['E', 'r', 'Compulsory', 'Unknown'])->map(fn ($status, $i) => collect([
        'code' => 'MAT20'.$i, 'title' => 'Math '.$i, 'credit_unit' => 3, 'semester' => 'First', 'level' => '100',
        'department_ids' => $f['department']->id, 'academic_session_id' => $f['session']->id, 'status' => $status,
    ]));
    $import->collection($rows);
    expect($import->createdCount())->toBe(3)->and($import->failedRows())->toHaveCount(1);
    expect(Courses::where('code', 'like', 'MAT%')->orderBy('code')->pluck('course_status')->all())->toBe(Courses::STATUSES);
    $import->collection(collect([collect(array_merge($rows[0]->all(), ['status' => 'C']))]));
    expect(Courses::where('code', 'MAT 200')->first()->course_status)->toBe('Compulsory');
});

it('includes status codes in every broadsheet section', function () {
    $f = courseStatusFixture();
    $results = $f['courses']->map(function ($course) use ($f) {
        $result = new Result(['course_code' => $course->code, 'department_id' => $course->department_id,
            'session' => $f['session']->name, 'semester' => 'First', 'level' => '100', 'user_id' => $f['student']->id,
            'attempt_type' => 'regular', 'workflow_status' => 'reviewed', 'outcome_status' => 'graded', 'score' => 65, 'grade' => 'B']);
        $result->setRelation('user', $f['student']);
        return $result;
    })->values();
    $rows = (new DepartmentalBroadsheetExport($results, 'Computing', $f['session']->name, 'First'))->array();
    foreach ([7, 14, 21] as $header) {
        expect($rows[$header])->toContain('CSC100 [E]', 'CSC101 [R]', 'CSC102 [C]');
    }
});
