<?php

use App\Models\{User, Faculty, Department};
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    config(['jetstream.profile_photo_disk' => 'public']);
    // A real image exercises both the embedded SVG and the PDF renderer.
    Storage::disk('public')->put('profile-photos/id-test.png', file_get_contents(public_path('asset/images/euvion.png')));
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computer Science', 'faculty_id' => $faculty->id]);
    $this->student = User::factory()->create(['usertype' => 'student', 'name' => 'Test Student', 'matric_number' => 'MUI/SCI/24/001', 'department_id' => $department->id, 'level' => '300']);
    $this->student->forceFill(['profile_photo_path' => 'profile-photos/id-test.png'])->save();
});

it('uses the approved validity period for each level', function ($level, $years) {
    $this->travelTo(\Carbon\Carbon::parse('2026-10-05'));
    $this->student->update(['level' => $level]);
    $this->actingAs($this->student)->get(route('student.id-card.index'))->assertOk()
        ->assertSee('Computer Science')->assertSee($level.' L')->assertSee('05 Oct '.(2026 + $years));
    expect($this->student->fresh()->temporary_id_expires_at)->toBeNull();
    $this->get(route('student.id-card.image'))->assertOk();
    expect($this->student->fresh()->temporary_id_expires_at->toDateString())->toBe((2026 + $years).'-10-05');
})->with([['100', 6], ['200', 4], ['300', 3], ['400', 2], ['500', 1], ['600', 1]]);

it('keeps an issued expiry fixed and blocks downloads after expiry', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-10-05'));
    $this->actingAs($this->student)->get(route('student.id-card.image'))->assertOk();
    $this->travelTo(\Carbon\Carbon::parse('2027-10-05'));
    $this->student->update(['level' => '400']);
    $this->get(route('student.id-card.image'))->assertOk()->assertSee('05 Oct 2029');
    expect($this->student->fresh()->temporary_id_expires_at->toDateString())->toBe('2029-10-05');
    $this->travelTo(\Carbon\Carbon::parse('2029-10-06'));
    $this->get(route('student.id-card.index'))->assertOk()->assertSee('Your temporary ID card has expired.');
    $this->get(route('student.id-card.image'))->assertStatus(422);
    $this->get(route('student.id-card.pdf'))->assertStatus(422);
});

it('requires a department and supported level before issuance', function () {
    $this->student->update(['department_id' => null, 'level' => '700']);
    $this->actingAs($this->student)->get(route('student.id-card.index'))->assertOk()->assertSee('valid student level');
    $this->get(route('student.id-card.image'))->assertStatus(422);
    expect($this->student->fresh()->temporary_id_expires_at)->toBeNull();
});

it('requires authentication and restricts card routes to students', function () {
    foreach (['index', 'image', 'pdf'] as $action) {
        $this->get(route('student.id-card.'.$action))->assertRedirect(route('login'));
    }
    $admin = User::factory()->create(['usertype' => 'admin']);
    foreach (['index', 'image', 'pdf'] as $action) {
        $this->actingAs($admin)->get(route('student.id-card.'.$action))->assertForbidden();
    }
});

it('shows the students own card and safely embeds stored images', function () {
    $other = User::factory()->create(['usertype' => 'student', 'name' => 'Private Other Student']);
    $this->actingAs($this->student)->get(route('student.id-card.index', ['student_id' => $other->id]))
        ->assertOk()->assertSee('MUDIAME UNIVERSITY')->assertSee('Test Student')->assertSee('MUI/SCI/24/001')
        ->assertDontSee('Private Other Student')->assertSee('Download image (PNG)')->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('student.id-card.image'))->assertOk()->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8')
        ->assertSee('data:image/png;base64,', false)->assertDontSee('profile-photos/id-test.png');
});

it('downloads a real PDF with the students identification', function () {
    $response = $this->actingAs($this->student)->get(route('student.id-card.pdf'));
    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF-');
    expect($response->headers->get('Content-Disposition'))->toContain('temporary-student-id-'.$this->student->id.'.pdf');
});

it('blocks downloads until matric number and a usable photo are present', function () {
    $this->student->forceFill(['matric_number' => null, 'profile_photo_path' => null])->save();
    $this->actingAs($this->student)->get(route('student.id-card.index'))->assertOk()
        ->assertSee('Your card needs a few details.')->assertSee('Not assigned')->assertSee('Photo required');
    $this->get(route('student.id-card.pdf'))->assertStatus(422);
    $this->get(route('student.id-card.image'))->assertStatus(422);
});

it('rejects arbitrary paths and escapes student text in the image', function () {
    $this->student->forceFill(['profile_photo_path' => '../private.png'])->save();
    $this->actingAs($this->student)->get(route('student.id-card.image'))->assertStatus(422);
    $this->student->forceFill(['profile_photo_path' => 'profile-photos/id-test.png', 'name' => '<script>alert(1)</script>'])->save();
    $this->get(route('student.id-card.image'))->assertOk()->assertDontSee('<script>', false)->assertSee('&lt;script&gt;', false);
});
