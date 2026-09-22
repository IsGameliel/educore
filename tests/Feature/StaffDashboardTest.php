<?php

use App\Models\AdmissionApplication;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;

it('sends assigned staff straight to their dashboard and blocks admission', function (string $role, string $view) {
    $staff = User::factory()->unverified()->create(['usertype' => $role]);
    $this->withSession(['url.intended' => route('admissions.create')])
        ->post('/login', ['email' => $staff->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
    $this->get(route('dashboard'))->assertOk()->assertViewIs($view);
    $this->get(route('admissions.create'))->assertRedirect(route('dashboard'));
    $this->post(route('admissions.store'), ['qualification' => 'Olevel'])
        ->assertRedirect(route('dashboard'));
    $this->get(route('verification.notice'))->assertRedirect(route('dashboard'));
    expect($staff->fresh()->usertype)->toBe($role)
        ->and($staff->fresh()->email_verified_at)->toBeNull()
        ->and(AdmissionApplication::where('user_id', $staff->id)->exists())->toBeFalse();
})->with([
    ['admin', 'admin.dashboard'], ['lecturer', 'lecturer.dashboard'],
    ['dean', 'staff.dashboard'], ['hod', 'staff.dashboard'],
    ['librarian', 'staff.dashboard'], ['admission_officer', 'staff.dashboard'],
    ['accountant', 'staff.dashboard'], ['bursar', 'staff.dashboard'],
    ['exam_officer', 'staff.dashboard'], ['vc', 'staff.dashboard'], ['registrar', 'staff.dashboard'],
    ['Dean', 'staff.dashboard'], ['HOD', 'staff.dashboard'], ['Liberian', 'staff.dashboard'],
    ['admission officer', 'staff.dashboard'], ['burser', 'staff.dashboard'], ['lectuer', 'lecturer.dashboard'],
]);

it('keeps applicant verification and admission onboarding', function () {
    $applicant = User::factory()->unverified()->create(['usertype' => 'user']);
    $this->actingAs($applicant)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    $this->get(route('admissions.create'))->assertRedirect(route('verification.notice'));
    $applicant->markEmailAsVerified();
    $this->get(route('dashboard'))->assertRedirect(route('admissions.create'));
    $this->get(route('admissions.create'))->assertOk();
});

it('does not grant staff administrative permissions or send onboarding codes', function () {
    Notification::fake();
    $staff = User::factory()->unverified()->create(['usertype' => 'admission_officer']);
    $this->actingAs($staff)->post(route('verification.send'))->assertRedirect(route('dashboard'));
    $this->get('/admin/staffs')->assertForbidden();
    Notification::assertNothingSent();
});

it('does not let unknown roles submit admission or acquire a student role', function () {
    $user = User::factory()->create(['usertype' => 'unknown_role']);
    $this->actingAs($user)->post(route('admissions.store'), [])->assertRedirect(route('dashboard'));
    $this->get(route('dashboard'))->assertForbidden();
    expect($user->fresh()->usertype)->toBe('unknown_role');
});

it('sends staff to their dashboard after two factor authentication', function () {
    $staff = User::factory()->unverified()->create(['usertype' => 'hod']);
    $this->actingAs($staff);
    $request = request();
    $request->setLaravelSession(app('session.store'));
    $request->session()->put('url.intended', route('admissions.create'));
    $response = app(TwoFactorLoginResponse::class)->toResponse($request);
    expect($response->getTargetUrl())->toBe(route('dashboard'));
});
