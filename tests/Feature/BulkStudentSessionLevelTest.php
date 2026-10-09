<?php

use App\Models\{AcademicSession, Department, Faculty, Result, StudentAcademicSession, User};

function bulkLevelFixture(): array
{
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $other = Department::create(['name' => 'Physics', 'faculty_id' => $faculty->id]);
    $session = AcademicSession::create(['name' => '2021/2022', 'start_year' => 2021, 'end_year' => 2022]);
    $admin = User::factory()->create(['usertype' => 'admin']);
    $students = User::factory()->count(24)->create(['usertype' => 'student', 'department_id' => $department->id, 'level' => '500', 'name' => 'Historical cohort']);
    $outside = User::factory()->create(['usertype' => 'student', 'department_id' => $other->id, 'level' => '500']);
    $differentLevel = User::factory()->create(['usertype' => 'student', 'department_id' => $department->id, 'level' => '400']);
    $payload = ['department_id' => $department->id, 'target_session' => $session->name, 'level' => '100', 'scope' => 'all_matching', 'current_level' => '500', 'q' => 'Historical cohort'];
    return compact('department', 'session', 'admin', 'students', 'outside', 'differentLevel', 'payload');
}

it('updates a filtered departmental cohort across every page while preserving current profiles', function () {
    $f = bulkLevelFixture();
    expect(config('database.default'))->toBe('sqlite')->and(config('database.connections.sqlite.database'))->toBe(':memory:');
    $this->actingAs($f['admin'])->get(route('admin.course-registrations.index', [
        'department_id' => $f['department']->id, 'current_level' => '500', 'q' => 'Historical cohort', 'session' => $f['session']->name,
    ]))->assertOk()->assertSee('All 24 students matching filters')->assertViewHas('students', fn ($students) => $students->total() === 24 && $students->count() === 20);
    $this->put(route('admin.course-registrations.bulk-session-level'), $f['payload'])->assertSessionHasNoErrors()->assertRedirect();
    expect(StudentAcademicSession::count())->toBe(24)
        ->and(StudentAcademicSession::where('level', '100')->count())->toBe(24)
        ->and(User::whereIn('id', $f['students']->pluck('id'))->where('level', '500')->count())->toBe(24);
    $this->assertDatabaseMissing('student_academic_sessions', ['user_id' => $f['outside']->id]);
    $this->assertDatabaseMissing('student_academic_sessions', ['user_id' => $f['differentLevel']->id]);
});

it('updates only ticked students and rejects students outside the filtered department', function () {
    $f = bulkLevelFixture();
    $this->actingAs($f['admin']);
    $url = route('admin.course-registrations.bulk-session-level');
    $payload = array_merge($f['payload'], ['scope' => 'selected', 'student_ids' => $f['students']->take(2)->pluck('id')->all()]);
    $this->put($url, $payload)->assertSessionHasNoErrors();
    expect(StudentAcademicSession::count())->toBe(2);
    $this->put($url, array_merge($payload, ['level' => '200', 'student_ids' => [$f['students'][0]->id, $f['outside']->id]]))->assertSessionHasErrors('student_ids');
    expect(StudentAcademicSession::where('level', '100')->count())->toBe(2);
    $this->put($url, array_merge($payload, ['student_ids' => []]))->assertSessionHasErrors('student_ids');
});

it('rejects conflicting academic history without partially updating the batch', function () {
    $f = bulkLevelFixture();
    $student = $f['students']->last();
    Result::create(['user_id' => $student->id, 'uploaded_by' => $f['admin']->id, 'matric_number' => 'BULK001',
        'department_id' => $f['department']->id, 'session' => $f['session']->name, 'semester' => 'First', 'level' => '200',
        'course_code' => 'GST 101', 'course_title' => 'General studies', 'credit_unit' => 2, 'score' => 70]);
    $this->actingAs($f['admin'])->put(route('admin.course-registrations.bulk-session-level'), $f['payload'])->assertSessionHasErrors('level');
    expect(StudentAcademicSession::count())->toBe(0)->and($student->fresh()->level)->toBe('500')->and(Result::sole()->level)->toBe('200');
    $this->actingAs($student)->put(route('admin.course-registrations.bulk-session-level'), $f['payload'])->assertForbidden();
});
