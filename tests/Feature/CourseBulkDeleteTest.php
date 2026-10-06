<?php

use App\Models\{AcademicSession, Courses, Department, Faculty, User};

it('allows admins to delete only selected courses and validates the entire selection', function () {
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $session = AcademicSession::create(['name' => '2026/2027', 'start_year' => 2026, 'end_year' => 2027]);
    $courses = collect(range(1, 3))->map(fn ($i) => Courses::create([
        'code' => 'CSC10'.$i, 'title' => 'Course '.$i, 'credit_unit' => 3, 'semester' => 'First',
        'department_id' => $department->id, 'academic_session_id' => $session->id, 'level' => '100',
    ]));
    $admin = User::factory()->create(['usertype' => 'admin']);
    $student = User::factory()->create(['usertype' => 'student']);
    $this->actingAs($student)->delete(route('admin.courses.bulk-delete'), ['course_ids' => [$courses[0]->id]])->assertForbidden();
    $this->actingAs($admin)->get(route('admin.courses.index'))->assertOk()->assertSee('Delete selected courses');
    $this->delete(route('admin.courses.bulk-delete'), ['course_ids' => [$courses[0]->id, 999999]])->assertSessionHasErrors('course_ids.1');
    expect(Courses::count())->toBe(3);
    $this->delete(route('admin.courses.bulk-delete'), ['course_ids' => []])->assertSessionHasErrors('course_ids');
    $this->delete(route('admin.courses.bulk-delete'), ['course_ids' => [$courses[0]->id, $courses[1]->id]])->assertRedirect()->assertSessionHas('success', '2 selected course(s) deleted successfully.');
    expect(Courses::pluck('id')->all())->toBe([$courses[2]->id]);
});
