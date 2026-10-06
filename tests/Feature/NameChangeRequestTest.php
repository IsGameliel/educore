<?php

use App\Models\{NameChangeRequest, User};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('requires evidence and admin approval to change a student name', function () {
    Storage::fake('local');
    $student = User::factory()->create(['usertype' => 'student', 'name' => 'Original Name']);
    $other = User::factory()->create(['usertype' => 'student']);
    $admin = User::factory()->create(['usertype' => 'admin']);
    $this->actingAs($student)->post(route('name-changes.store'), ['requested_name' => 'Correct Name', 'reason' => 'My original name was misspelled.'])->assertSessionHasErrors('document');
    $data = ['requested_name' => 'Correct Name', 'reason' => 'My original name was misspelled.', 'document' => UploadedFile::fake()->create('evidence.pdf', 20, 'application/pdf')];
    $this->post(route('name-changes.store'), $data)->assertSessionHasNoErrors();
    $change = NameChangeRequest::sole();
    expect($student->fresh()->name)->toBe('Original Name');
    Storage::disk('local')->assertExists($change->document_path);
    $data['document'] = UploadedFile::fake()->create('second-evidence.pdf', 20, 'application/pdf');
    $this->post(route('name-changes.store'), $data)->assertSessionHasErrors('requested_name');
    $this->post(route('name-changes.review', $change), ['decision' => 'approved', 'review_note' => 'Evidence verified.'])->assertForbidden();
    $this->actingAs($other)->get(route('name-changes.document', $change))->assertForbidden();
    $this->get(route('name-changes.index'))->assertDontSee('Correct Name');
    $this->actingAs($admin)->get(route('name-changes.index'))->assertOk()->assertSee('Correct Name');
    $this->get(route('name-changes.document', $change))->assertOk();
    $this->post(route('name-changes.review', $change), ['decision' => 'approved', 'review_note' => 'Evidence verified.'])->assertSessionHasNoErrors();
    expect($student->fresh()->name)->toBe('Correct Name')->and($change->fresh()->status)->toBe('approved');
    $this->post(route('name-changes.review', $change), ['decision' => 'approved', 'review_note' => 'Evidence verified.'])->assertStatus(409);
});

it('preserves protected student fields in forged profile requests', function () {
    $student = User::factory()->create(['usertype' => 'student', 'name' => 'Original Name', 'level' => '200']);
    $this->actingAs($student)->put(route('user-profile-information.update'), [
        'name' => 'Forged Name', 'email' => $student->email, 'department_id' => 99999, 'level' => '600',
    ])->assertSessionHasNoErrors();
    expect($student->fresh()->name)->toBe('Original Name')->and($student->fresh()->level)->toBe('200')
        ->and($student->fresh()->department_id)->toBe($student->department_id);
});
