<?php

use App\Models\AdmissionApplication;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\Payment;

beforeEach(function () {
    config(['services.paystack.secret_key' => 'sk_test_example']);
    Http::preventStrayRequests();
    Http::fake(['api.paystack.co/transaction/initialize' => fn ($request) => Http::response([
        'status' => true, 'data' => ['reference' => $request['reference'], 'authorization_url' => 'https://checkout.paystack.com/test-checkout'],
    ])]);
    Storage::fake('public');
    $this->applicant = User::factory()->create(['usertype' => 'applicant']);
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computer Science', 'faculty_id' => $faculty->id]);
    $this->payload = [
        'surname' => 'Smith', 'first_name' => 'Jane', 'date_of_birth' => '2004-01-01',
        'gender' => 'Female', 'phone' => '08012345678', 'address' => '1 School Road',
        'country' => 'Nigeria', 'parent_name' => 'John Smith', 'parent_relationship' => 'Father',
        'parent_phone' => '08012345679', 'admission_type' => 'fresh',
        'department_id' => $department->id, 'level' => '100', 'entry_year' => now()->year,
        'previous_school' => 'Example School', 'qualification' => 'Olevel',
        'olevel_subjects' => ['Mathematics', 'English Language'], 'olevel_grades' => ['A1', 'B2'],
    ];
});

it('renders the admission form and restores subject entries after errors', function () {
    $this->actingAs($this->applicant)->withSession(['_old_input' => $this->payload])
        ->get(route('admissions.create'))->assertOk()
        ->assertSee('Select qualification')->assertSee('Add Subject')
        ->assertSee('JAMB Result')->assertSee('Diploma Result/Certificate')->assertSee('Transcript')
        ->assertSee('Foreign Student')->assertSee('Mathematics');
});

it('submits each applicant type with academic records and documents', function ($type, $qualification) {
    $payload = array_merge($this->payload, [
        'admission_type' => $type, 'qualification' => $qualification,
        'jamb_result' => UploadedFile::fake()->create('jamb.pdf', 10, 'application/pdf'),
        'diploma_result' => UploadedFile::fake()->create('certificate.pdf', 10, 'application/pdf'),
        'transcript' => UploadedFile::fake()->create('transcript.pdf', 10, 'application/pdf'),
    ]);
    $this->actingAs($this->applicant)->post(route('admissions.store'), $payload)
        ->assertSessionHasNoErrors()->assertRedirect('https://checkout.paystack.com/test-checkout');
    $application = AdmissionApplication::where('user_id', $this->applicant->id)->sole();
    expect($application->admission_type)->toBe($type)
        ->and($application->qualification)->toBe($qualification)
        ->and($this->applicant->fresh()->usertype)->toBe('applicant')
        ->and($application->status)->toBe('awaiting_payment')
        ->and($application->completed_at)->toBeNull();
    $payment = Payment::sole();
    expect($payment->amount)->toBe(1000000);
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response([
        'status' => true, 'data' => ['id' => 123456, 'reference' => $payment->reference, 'status' => 'success',
            'amount' => 1000000, 'currency' => 'NGN', 'domain' => 'test', 'customer' => ['email' => $payment->email]],
    ])]);
    $this->get(route('payments.callback', ['reference' => $payment->reference]))->assertRedirect(route('dashboard'));
    expect($this->applicant->fresh()->usertype)->toBe('student')
        ->and($application->fresh()->status)->toBe('completed')
        ->and($payment->fresh()->status)->toBe('success');
    if ($qualification === 'Olevel') {
        expect($application->olevel_results)->toBe([
            ['subject' => 'Mathematics', 'grade' => 'A1'],
            ['subject' => 'English Language', 'grade' => 'B2'],
        ]);
    } else {
        expect($application->olevel_results)->toBeNull();
    }
    foreach (['jamb_result_path', 'diploma_result_path', 'transcript_path'] as $column) {
        Storage::disk('public')->assertExists($application->{$column});
    }
})->with([['fresh', 'Olevel'], ['direct_entry', 'ND'], ['transfer', 'Diploma'], ['foreign', 'Olevel']]);

it('rejects incomplete duplicate or invalid Olevel results', function ($subjects, $grades, $error) {
    $this->actingAs($this->applicant)->post(route('admissions.store'), array_merge($this->payload, [
        'olevel_subjects' => $subjects, 'olevel_grades' => $grades,
    ]))->assertSessionHasErrors($error);
    expect(AdmissionApplication::count())->toBe(0);
})->with([
    [[], [], 'olevel_subjects'],
    [['Mathematics', 'English Language'], ['A1', ''], 'olevel_grades.1'],
    [['Mathematics', 'English Language'], ['A1'], 'olevel_subjects'],
    [['Mathematics', 'Mathematics'], ['A1', 'B2'], 'olevel_subjects.0'],
    [['Mathematics'], ['X1'], 'olevel_grades.0'],
]);

it('requires country for foreign applicants', function () {
    $this->actingAs($this->applicant)->post(route('admissions.store'), array_merge($this->payload, [
        'admission_type' => 'foreign', 'country' => '',
    ]))->assertSessionHasErrors('country');
});

it('preserves applicant access until payment and reuses a saved application', function () {
    $this->actingAs($this->applicant);
    $this->post(route('admissions.store'), $this->payload)->assertRedirect('https://checkout.paystack.com/test-checkout');
    $this->post(route('admissions.store'), $this->payload)->assertRedirect('https://checkout.paystack.com/test-checkout');
    expect(Payment::count())->toBe(1)->and(AdmissionApplication::count())->toBe(1)
        ->and($this->applicant->fresh()->usertype)->toBe('applicant');
    Http::assertSentCount(1);
    $this->get(route('admissions.create'))->assertOk()->assertSee('Continue payment');
    $this->get(route('payments.show', Payment::sole()))->assertOk()->assertSee('10,000');
});

it('keeps the complete admission form saved when the payment gateway is unavailable', function () {
    Http::swap(new \Illuminate\Http\Client\Factory);
    Http::preventStrayRequests();
    Http::fake(['api.paystack.co/transaction/initialize' => Http::response([], 503)]);
    $this->actingAs($this->applicant)->post(route('admissions.store'), $this->payload)->assertSessionHasErrors('payment');
    expect(AdmissionApplication::sole()->status)->toBe('awaiting_payment')
        ->and($this->applicant->fresh()->usertype)->toBe('applicant');
});

it('rejects unsupported and oversized uploads', function () {
    $this->actingAs($this->applicant)->post(route('admissions.store'), array_merge($this->payload, [
        'jamb_result' => UploadedFile::fake()->create('jamb.pdf', 5121, 'application/pdf'),
        'transcript' => UploadedFile::fake()->create('transcript.txt', 1, 'text/plain'),
    ]))->assertSessionHasErrors(['jamb_result', 'transcript']);
    expect(AdmissionApplication::count())->toBe(0);
});
