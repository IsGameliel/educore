<?php

use App\Models\User;
use App\Notifications\EmailVerificationOtp;
use App\Services\EmailVerificationOtpService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Jetstream\Jetstream;
use Symfony\Component\Mailer\Exception\TransportException;

beforeEach(function () {
    Notification::fake();
    $this->user = User::factory()->unverified()->create();
});

function sentEmailCode(User $user): string
{
    return Notification::sent($user, EmailVerificationOtp::class)->last()->code;
}

test('registration sends a code and redirects to verification', function () {
    $this->post('/register', [
        'name' => 'Applicant', 'email' => 'applicant@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ])->assertRedirect(route('verification.notice'));
    $user = User::where('email', 'applicant@example.com')->sole();
    Notification::assertSentTo($user, EmailVerificationOtp::class);
    expect($user->hasVerifiedEmail())->toBeFalse()
        ->and(Hash::check(sentEmailCode($user), $user->email_otp_hash))->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

test('email verification screen can be rendered', function () {
    $this->actingAs($this->user)->get('/email/verify')->assertOk()->assertSee('Verification code');
});

test('unverified users cannot access or submit admissions even after logging in', function () {
    $this->actingAs($this->user)->get('/home')->assertRedirect(route('verification.notice'));
    $this->get('/admission')->assertRedirect(route('verification.notice'));
    $this->post('/admission', [])->assertRedirect(route('verification.notice'));
    $this->post('/logout');
    $this->post('/login', ['email' => $this->user->email, 'password' => 'password']);
    $this->get('/admission')->assertRedirect(route('verification.notice'));
});

test('correct code verifies email clears the code and permits admission', function () {
    $this->user->sendEmailVerificationNotification();
    $code = sentEmailCode($this->user);
    Event::fake([Verified::class]);
    $this->actingAs($this->user)->post(route('verification.otp'), ['code' => $code])
        ->assertRedirect(route('dashboard'));
    expect($this->user->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and($this->user->fresh()->email_otp_hash)->toBeNull();
    Event::assertDispatched(Verified::class);
    $this->get('/admission')->assertOk();
    expect(app(EmailVerificationOtpService::class)->verify($this->user, $code))->toBeFalse();
});

test('incorrect expired and malformed codes cannot verify email', function () {
    $this->user->sendEmailVerificationNotification();
    $code = sentEmailCode($this->user);
    $wrong = $code === '000000' ? '111111' : '000000';
    $this->actingAs($this->user)->post(route('verification.otp'), ['code' => $wrong])->assertSessionHasErrors('code');
    $this->post(route('verification.otp'), ['code' => 'abc'])->assertSessionHasErrors('code');
    $this->travel(10)->minutes();
    $this->post(route('verification.otp'), ['code' => $code])->assertSessionHasErrors('code');
    expect($this->user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('five failed attempts invalidate a code', function () {
    $this->user->sendEmailVerificationNotification();
    $code = sentEmailCode($this->user);
    $service = app(EmailVerificationOtpService::class);
    for ($i = 0; $i < 5; $i++) {
        expect($service->verify($this->user, $code === '000000' ? '111111' : '000000'))->toBeFalse();
    }
    expect($service->verify($this->user, $code))->toBeFalse()
        ->and($this->user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('resending has a cooldown and replaces the previous code', function () {
    $this->user->sendEmailVerificationNotification();
    $oldHash = $this->user->fresh()->email_otp_hash;
    $this->actingAs($this->user)->post(route('verification.send'))->assertSessionHasErrors('code');
    Notification::assertSentToTimes($this->user, EmailVerificationOtp::class, 1);
    $this->travel(61)->seconds();
    $this->post(route('verification.send'))->assertSessionHas('status', 'verification-code-sent');
    Notification::assertSentToTimes($this->user, EmailVerificationOtp::class, 2);
    expect($this->user->fresh()->email_otp_hash)->not->toBe($oldHash);
    $this->post(route('verification.otp'), ['code' => sentEmailCode($this->user)])->assertSessionHasNoErrors();
});

test('codes are tied to the account and current email', function () {
    $this->user->sendEmailVerificationNotification();
    $code = sentEmailCode($this->user);
    $other = User::factory()->unverified()->create();
    expect(app(EmailVerificationOtpService::class)->verify($other, $code))->toBeFalse();
    $this->user->forceFill(['email' => 'changed@example.com'])->save();
    expect(app(EmailVerificationOtpService::class)->verify($this->user, $code))->toBeFalse();
});

test('legacy verification links cannot bypass the code requirement', function () {
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $this->user->id, 'hash' => sha1($this->user->email),
    ]);
    $this->actingAs($this->user)->get($url)->assertNotFound();
    expect($this->user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verification endpoints require authentication and throttle guesses', function () {
    $this->post(route('verification.otp'), ['code' => '123456'])->assertRedirect(route('login'));
    $this->post(route('verification.send'))->assertRedirect(route('login'));
    $this->actingAs($this->user);
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('verification.otp'), ['code' => '123456'])->assertSessionHasErrors('code');
    }
    $this->post(route('verification.otp'), ['code' => '123456'])->assertStatus(429);
});

test('mail failures leave registration recoverable through resend', function () {
    Notification::shouldReceive('send')->andThrow(new TransportException('Unavailable'));
    $this->post('/register', [
        'name' => 'Applicant', 'email' => 'retry@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ])->assertRedirect(route('verification.notice'))->assertSessionHas('otp_error');
    $user = User::where('email', 'retry@example.com')->sole();
    $this->assertAuthenticatedAs($user);
    expect($user->email_otp_hash)->toBeNull()->and($user->hasVerifiedEmail())->toBeFalse();
    Notification::fake();
    $this->post(route('verification.send'))->assertSessionHas('status', 'verification-code-sent');
    Notification::assertSentTo($user, EmailVerificationOtp::class);
});

test('codes starting with zero can be verified', function () {
    $this->user->forceFill([
        'email_otp_hash' => Hash::make('012345'), 'email_otp_address' => $this->user->email,
        'email_otp_expires_at' => now()->addMinutes(10),
    ])->save();
    $this->actingAs($this->user)->post(route('verification.otp'), ['code' => '012345'])
        ->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
    expect($this->user->fresh()->hasVerifiedEmail())->toBeTrue();
});
