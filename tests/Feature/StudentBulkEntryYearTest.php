<?php

use App\Models\{Department, Faculty, User};

it('bulk updates only matching active students across pages and records changes', function () {
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $otherDepartment = Department::create(['name' => 'Physics', 'faculty_id' => $faculty->id]);
    $admin = User::factory()->create(['usertype' => 'admin']);
    $matching = User::factory()->count(16)->create(['usertype' => 'student', 'name' => 'Target Student', 'level' => '200', 'department_id' => $department->id, 'entry_year' => 2026]);
    $excluded = collect([
        User::factory()->create(['usertype' => 'student', 'name' => 'Target Student', 'level' => '100', 'department_id' => $department->id, 'entry_year' => 2026]),
        User::factory()->create(['usertype' => 'student', 'name' => 'Target Student', 'level' => '200', 'department_id' => $otherDepartment->id, 'entry_year' => 2026]),
        User::factory()->create(['usertype' => 'student', 'name' => 'Other Student', 'level' => '200', 'department_id' => $department->id, 'entry_year' => 2026]),
        User::factory()->create(['usertype' => 'lecturer', 'name' => 'Target Student', 'level' => '200', 'department_id' => $department->id, 'entry_year' => 2026]),
    ]);
    $filters = ['name' => 'Target', 'department' => $department->id, 'level' => '200'];
    $this->actingAs($admin)->get(route('admin.students.index', $filters))->assertOk()->assertSee('Bulk edit entry year')->assertSee('16 students matching');
    $this->post(route('admin.students.bulk-entry-year'), $filters + ['entry_year' => 2024, 'page' => 2])
        ->assertRedirect(route('admin.students.index', $filters))->assertSessionHas('success', 'Entry year updated to 2024 for 16 student(s).');
    foreach ($matching as $student) { expect((int) $student->fresh()->entry_year)->toBe(2024); }
    foreach ($excluded as $student) { expect((int) $student->fresh()->entry_year)->toBe(2026); }
    expect(\App\Models\ActivityLog::where('action', 'student_entry_year_updated')->count())->toBe(16);
    $this->post(route('admin.students.bulk-entry-year'), $filters + ['entry_year' => 2200])->assertSessionHasErrors('entry_year');
    $this->post(route('admin.students.bulk-entry-year'), ['level' => 'invalid', 'entry_year' => 2024])->assertSessionHasErrors('level');
    $this->actingAs($matching->first())->post(route('admin.students.bulk-entry-year'), ['entry_year' => 2024])->assertForbidden();
});
