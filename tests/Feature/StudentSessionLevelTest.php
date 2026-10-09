<?php

use App\Imports\ResultsImport;
use App\Models\{AcademicSession, CourseRegistration, Courses, Department, Faculty, Result, User};
use App\Services\Academic\StudentSessionLevel;

it('uploads historical results with each saved session level without changing the current level', function () {
    expect(config('database.default'))->toBe('sqlite');
    expect(config('database.connections.sqlite.database'))->toBe(':memory:');
    AcademicSession::create(['name' => '2025/2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $admin = User::factory()->create(['usertype' => 'admin']);
    $student = User::factory()->create(['usertype' => 'student', 'level' => '500', 'department_id' => $department->id, 'matric_number' => 'HIST001']);
    $this->actingAs($admin);
    foreach (['2021/2022' => '100', '2022/2023' => '200'] as $name => $level) {
        $session = AcademicSession::create(['name' => $name, 'start_year' => (int) $name, 'end_year' => (int) $name + 1]);
        $course = Courses::create(['code' => 'GST 101', 'title' => 'General Studies', 'level' => '100', 'semester' => 'First', 'credit_unit' => 2, 'department_id' => $department->id, 'academic_session_id' => $session->id]);
        expect(StudentSessionLevel::find($student, $name))->toBeNull();
        $url = route('admin.course-registrations.session-level', $student);
        $this->put($url, ['session' => $name, 'semester' => 'First', 'level' => $level])->assertSessionHasNoErrors()->assertRedirect();
        $this->get(route('admin.course-registrations.edit', ['student' => $student->id, 'session' => $name]))
            ->assertOk()->assertViewHas('sessionLevel', $level);
        CourseRegistration::create(['user_id' => $student->id, 'course_id' => $course->id, 'semester' => 'First', 'session' => $name, 'status' => 'registered', 'registration_date' => now()]);
        $import = new ResultsImport($course, $admin->id, $name, 'First');
        $import->importRows(collect([
            ['University result sheet'], ['School', 'Science'], ['Department', 'Computing'],
            ['Level', '100'], ['Course code', 'GST 101'], ['Course title', 'General Studies'], ['Semester', 'First'],
            ['S/NO', 'MATRIC NO.', 'NAME', 'CA', 'EXAM', 'Total'],
            [1, 'HIST001', $student->name, 20, 50, 70],
        ]));
        expect(Result::where('session', $name)->sole()->level)->toBe($level)
            ->and($student->fresh()->level)->toBe('500');
        $result = Result::where('session', $name)->sole();
        $this->get(route('admin.results.edit', $result))->assertOk()
            ->assertViewHas('sessionLevels', fn ($levels) => $levels[$student->id][$name] === $level);
        $this->put(route('admin.results.update', $result), [
            'user_id' => $student->id, 'session' => $name, 'semester' => 'First',
            'course_code' => $course->code, 'course_title' => $course->title, 'credit_unit' => 2,
            'score' => 80, 'department_id' => $department->id,
        ])->assertSessionHasNoErrors()->assertRedirect();
        expect($result->fresh()->level)->toBe($level)
            ->and(\App\Services\Academic\StudentCreditLimit::for($student, $name, 'First'))->toBe(24);
        $this->put($url, ['session' => $name, 'semester' => 'First', 'level' => '300'])->assertSessionHasErrors('level');
        expect(StudentSessionLevel::find($student, $name))->toBe($level);
    }
    expect(StudentSessionLevel::find($student, '2025/2026'))->toBe('500');
});

it('requires a historical session level and rejects unauthorized changes', function () {
    $session = AcademicSession::create(['name' => '2021/2022', 'start_year' => 2021, 'end_year' => 2022]);
    $student = User::factory()->create(['usertype' => 'student', 'level' => '500']);
    expect(fn () => StudentSessionLevel::require($student, $session->name))->toThrow(\Illuminate\Validation\ValidationException::class);
    $this->actingAs($student)->put(route('admin.course-registrations.session-level', $student), [
        'session' => $session->name, 'semester' => 'First', 'level' => '100',
    ])->assertForbidden();
});
