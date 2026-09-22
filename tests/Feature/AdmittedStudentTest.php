<?php

use App\Models\AdmissionApplication;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->create(['usertype' => 'admin']);
    $this->student = User::factory()->create(['usertype' => 'student', 'name' => 'Jane Smith', 'matric_number' => 'MAT-123']);
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computer Science', 'faculty_id' => $faculty->id]);
    $this->application = AdmissionApplication::create([
        'user_id' => $this->student->id, 'application_number' => 'ADM-2026-123',
        'status' => 'completed', 'admission_type' => 'fresh',
        'surname' => 'Smith', 'first_name' => 'Jane', 'date_of_birth' => '2004-01-01',
        'gender' => 'Female', 'phone' => '08012345678', 'address' => '1 School Road',
        'parent_name' => 'John Smith', 'parent_relationship' => 'Father', 'parent_phone' => '08012345679',
        'department_id' => $department->id, 'level' => '100', 'entry_year' => 2026,
        'previous_school' => 'Example School', 'qualification' => 'Olevel',
        'olevel_results' => [['subject' => 'Mathematics', 'grade' => 'A1']],
        'completed_at' => now(), 'jamb_result_path' => 'admission-documents/jamb.pdf',
    ]);
    Storage::disk('public')->put('admission-documents/jamb.pdf', '%PDF-1.4 test document');
});

it('lists completed admissions and filters by student identifiers and admission fields', function () {
    $draft = $this->application->replicate();
    $draft->application_number = 'DRAFT-ONLY';
    $draft->status = 'draft';
    $draft->save();
    $this->actingAs($this->admin)->get(route('admin.admitted-students.index'))
        ->assertOk()->assertSee('Admitted Students')->assertSee('ADM-2026-123')->assertDontSee('DRAFT-ONLY');
    foreach (['Jane Smith', 'MAT-123', $this->student->email, 'ADM-2026-123'] as $search) {
        $this->get(route('admin.admitted-students.index', ['search' => $search]))
            ->assertOk()->assertSee('ADM-2026-123');
    }
    $this->get(route('admin.admitted-students.index', [
        'department_id' => $this->application->department_id, 'admission_type' => 'fresh', 'entry_year' => 2026,
    ]))->assertOk()->assertSee('ADM-2026-123');
    foreach ([['search' => 'unknown'], ['admission_type' => 'transfer'], ['entry_year' => 2025]] as $filters) {
        $this->get(route('admin.admitted-students.index', $filters))
            ->assertOk()->assertDontSee('ADM-2026-123')->assertSee('No admitted students found');
    }
});

it('shows the complete admission record and document availability', function () {
    $this->actingAs($this->admin)->get(route('admin.admitted-students.show', $this->application))
        ->assertOk()->assertSee('Jane')->assertSee('John Smith')->assertSee('1 School Road')
        ->assertSee('Computer Science')->assertSee('Science')->assertSee('Example School')
        ->assertSee('Mathematics')->assertSee('A1')->assertSee('MAT-123')
        ->assertSee('JAMB Result')->assertSee('Not uploaded')->assertSee('Download');
});

it('serves documents inline or as downloads and handles missing or unknown documents', function () {
    $url = route('admin.admitted-students.document', [$this->application, 'jamb_result']);
    $this->actingAs($this->admin)->get($url)->assertOk()
        ->assertHeader('content-disposition', 'inline; filename=jamb_result.pdf');
    $this->get($url.'?download=1')->assertOk()->assertDownload('jamb_result.pdf');
    $this->get(route('admin.admitted-students.document', [$this->application, 'transcript']))->assertNotFound();
    $this->get(route('admin.admitted-students.document', [$this->application, 'unknown']))->assertNotFound();
    Storage::disk('public')->delete($this->application->jamb_result_path);
    $this->get($url)->assertNotFound();
    $this->get(route('admin.admitted-students.show', $this->application))->assertOk()->assertSee('File unavailable');
});

it('blocks non-admin access to the list details and documents', function ($role) {
    $user = User::factory()->create(['usertype' => $role]);
    $this->actingAs($user);
    $this->get(route('admin.admitted-students.index'))->assertForbidden();
    $this->get(route('admin.admitted-students.show', $this->application))->assertForbidden();
    $this->get(route('admin.admitted-students.document', [$this->application, 'jamb_result']))->assertForbidden();
})->with(['student', 'applicant', 'lecturer']);

it('does not expose incomplete admission details or documents', function () {
    $this->application->update(['status' => 'draft']);
    $this->actingAs($this->admin)->get(route('admin.admitted-students.show', $this->application))->assertNotFound();
    $this->get(route('admin.admitted-students.document', [$this->application, 'jamb_result']))->assertNotFound();
});
