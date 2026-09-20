<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\EmailVerificationOtp;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmailVerificationOtpService
{
    public function send(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $account = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($account->hasVerifiedEmail() || (
                $account->email_otp_address === $account->email
                && $account->email_otp_sent_at?->gt(now()->subMinute())
            )) {
                return false;
            }

            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $account->forceFill([
                'email_otp_hash' => Hash::make($code),
                'email_otp_address' => $account->email,
                'email_otp_expires_at' => now()->addMinutes(10),
                'email_otp_sent_at' => now(),
                'email_otp_attempts' => 0,
            ])->save();
            // Roll back the new code if delivery to the mail transport fails.
            $account->notify(new EmailVerificationOtp($code));

            return true;
        });
    }

    public function verify(User $user, string $code): bool
    {
        $verified = DB::transaction(function () use ($user, $code) {
            $account = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($account->hasVerifiedEmail() || ! $account->email_otp_hash
                || $account->email_otp_address !== $account->email
                || ! $account->email_otp_expires_at?->isFuture()
                || $account->email_otp_attempts >= 5) {
                return false;
            }

            if (! Hash::check($code, $account->email_otp_hash)) {
                $account->increment('email_otp_attempts');

                return false;
            }

            $account->forceFill([
                'email_verified_at' => now(),
                'email_otp_hash' => null,
                'email_otp_address' => null,
                'email_otp_expires_at' => null,
                'email_otp_sent_at' => null,
                'email_otp_attempts' => 0,
            ])->save();

            return true;
        });

        if ($verified) {
            event(new Verified($user->refresh()));
        }

        return $verified;
    }
}
