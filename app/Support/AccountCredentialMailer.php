<?php

namespace App\Support;

use App\Mail\UserAccountCredentials;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AccountCredentialMailer
{
    public static function send(User $user, ?string $plainPassword, string $action = 'created'): void
    {
        try {
            Mail::to($user->email)->send(new UserAccountCredentials($user, $plainPassword, $action));
        } catch (\Throwable $exception) {
            Log::error('Failed to send account credentials email.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'action' => $action,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }
}
