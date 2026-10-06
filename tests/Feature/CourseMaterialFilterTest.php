<?php

use App\Models\Courses;
use App\Models\CourseMaterial;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $this->department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $this->course = Courses::create([
        'code' => 'CSC101', 'title' => 'Computing', 'credit_unit' => 3,
        'department_id' => $this->department->id, 'level' => '100', 'semester' => 'First',
    ]);
    $this->actingAs(User::factory()->create(['usertype' => 'admin']));
    $this->payload = [
        'title' => 'Lecture notes', 'level' => '100', 'semester' => 'First',
        'department_id' => $this->department->id, 'course_id' => $this->course->id,
    ];
});

it('uploads materials for a course matching the department and semester', function () {
    $this->get(route('admin.course-materials.create'))->assertOk()->assertSee('Choose Department')->assertSee('data-department', false);
    $this->post(route('admin.course-materials.store'), $this->payload + ['file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])
        ->assertSessionHasNoErrors()->assertRedirect();
    $material = CourseMaterial::firstOrFail();
    expect($material->course_id)->toEqual($this->course->id);
    Storage::disk('public')->assertExists($material->file_path);
    $this->get(route('admin.course-materials.edit', $material))->assertOk()->assertSee('data-semester="First"', false);
    $this->put(route('admin.course-materials.update', $material), $this->payload)->assertSessionHasNoErrors()->assertRedirect();
});

it('loads student course materials for the student department and level', function () {
    CourseMaterial::create($this->payload + ['file_path' => 'materials/notes.pdf']);
    CourseMaterial::create(array_replace($this->payload, ['title' => 'Other level notes', 'level' => '200']) + ['file_path' => 'materials/other.pdf']);
    $student = User::factory()->create(['usertype' => 'student', 'department_id' => $this->department->id, 'level' => '100']);
    $this->actingAs($student)->get(route('student.course-materials'))->assertOk()
        ->assertSee('Lecture notes')->assertDontSee('Other level notes');
});

it('rejects courses from another department or semester before storing files', function () {
    $other = Department::create(['name' => 'Physics', 'faculty_id' => $this->department->faculty_id]);
    foreach ([['department_id' => $other->id], ['semester' => 'Second']] as $change) {
        $this->post(route('admin.course-materials.store'), array_replace($this->payload, $change, ['file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('course_id');
    }
    $this->assertDatabaseCount('course_materials', 0);
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

it('rejects a mismatched course when editing existing material', function () {
    $material = CourseMaterial::create($this->payload + ['file_path' => 'course_materials/notes.pdf']);
    $this->put(route('admin.course-materials.update', $material), array_replace($this->payload, ['semester' => 'Second']))
        ->assertSessionHasErrors('course_id');
    expect($material->fresh()->semester)->toBe('First');
});
