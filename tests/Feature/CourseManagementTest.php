<?php

use App\Models\AcademicSession;
use App\Models\Courses;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;

function createCourseDepartment(): Department
{
    $faculty = Faculty::create([
        'name' => 'Faculty of Science',
        'code' => 'FOS',
    ]);

    return Department::create([
        'name' => 'Computer Science',
        'faculty_id' => $faculty->id,
    ]);
}

function validCoursePayload(Department $department, AcademicSession $academicSession, array $overrides = []): array
{
    return array_merge([
        'code' => 'CSC101',
        'title' => 'Introduction to Computing',
        'credit_unit' => 3,
        'semester' => 'First',
        'department_ids' => [$department->id],
        'level' => '100',
        'academic_session_id' => $academicSession->id,
    ], $overrides);
}

it('lets admins create a course with an academic session', function () {
    $admin = User::factory()->create([
        'usertype' => 'admin',
    ]);

    $department = createCourseDepartment();
    $academicSession = AcademicSession::create([
        'name' => '2025/2026',
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.courses.store'), validCoursePayload($department, $academicSession))
        ->assertRedirect(route('admin.courses.index'))
        ->assertSessionHas('success', 'Course created successfully for 1 department.');

    $this->assertDatabaseHas('courses', [
        'code' => 'CSC101',
        'department_id' => $department->id,
        'level' => '100',
        'semester' => 'First',
        'academic_session_id' => $academicSession->id,
    ]);
});

it('requires an academic session when creating a course', function () {
    $admin = User::factory()->create([
        'usertype' => 'admin',
    ]);

    $department = createCourseDepartment();
    $academicSession = AcademicSession::create([
        'name' => '2025/2026',
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
    ]);

    $payload = validCoursePayload($department, $academicSession);
    unset($payload['academic_session_id']);

    $this->actingAs($admin)
        ->from(route('admin.courses.create'))
        ->post(route('admin.courses.store'), $payload)
        ->assertRedirect(route('admin.courses.create'))
        ->assertSessionHasErrors('academic_session_id');

    expect(Courses::query()->count())->toBe(0);
});

it('allows the same course code semester level and department in different academic sessions', function () {
    $admin = User::factory()->create([
        'usertype' => 'admin',
    ]);

    $department = createCourseDepartment();
    $oldSession = AcademicSession::create([
        'name' => '2024/2025',
        'start_year' => 2024,
        'end_year' => 2025,
        'is_active' => false,
    ]);
    $currentSession = AcademicSession::create([
        'name' => '2025/2026',
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.courses.store'), validCoursePayload($department, $oldSession))
        ->assertRedirect(route('admin.courses.index'));

    $this->actingAs($admin)
        ->post(route('admin.courses.store'), validCoursePayload($department, $currentSession))
        ->assertRedirect(route('admin.courses.index'));

    expect(Courses::where('code', 'CSC101')
        ->where('semester', 'First')
        ->where('level', '100')
        ->where('department_id', $department->id)
        ->count())->toBe(2);

    $this->assertDatabaseHas('courses', [
        'code' => 'CSC101',
        'department_id' => $department->id,
        'academic_session_id' => $oldSession->id,
    ]);
    $this->assertDatabaseHas('courses', [
        'code' => 'CSC101',
        'department_id' => $department->id,
        'academic_session_id' => $currentSession->id,
    ]);
});

it('edits only matching course records in the same academic session', function () {
    $admin = User::factory()->create([
        'usertype' => 'admin',
    ]);

    $department = createCourseDepartment();
    $oldSession = AcademicSession::create([
        'name' => '2024/2025',
        'start_year' => 2024,
        'end_year' => 2025,
        'is_active' => false,
    ]);
    $currentSession = AcademicSession::create([
        'name' => '2025/2026',
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
    ]);

    $oldSessionCourse = Courses::create([
        'code' => 'CSC101',
        'title' => 'Introduction to Computing',
        'credit_unit' => 3,
        'semester' => 'First',
        'department_id' => $department->id,
        'level' => '100',
        'academic_session_id' => $oldSession->id,
    ]);

    $currentSessionCourse = Courses::create([
        'code' => 'CSC101',
        'title' => 'Introduction to Computing',
        'credit_unit' => 3,
        'semester' => 'First',
        'department_id' => $department->id,
        'level' => '100',
        'academic_session_id' => $currentSession->id,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.courses.update', $currentSessionCourse), validCoursePayload($department, $currentSession, [
            'title' => 'Computing Fundamentals',
            'credit_unit' => 4,
        ]))
        ->assertRedirect(route('admin.courses.index'))
        ->assertSessionHas('success');

    expect($currentSessionCourse->fresh()?->title)->toBe('Computing Fundamentals')
        ->and($currentSessionCourse->fresh()?->credit_unit)->toBe(4)
        ->and($oldSessionCourse->fresh()?->title)->toBe('Introduction to Computing')
        ->and($oldSessionCourse->fresh()?->credit_unit)->toBe(3);
});
