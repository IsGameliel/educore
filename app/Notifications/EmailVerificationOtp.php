<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationOtp extends Notification
{
    public function __construct(public readonly string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Educore email verification code')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Enter this code on the email verification page to continue:')
            ->line($this->code)
            ->line('This code expires in 10 minutes. Do not share it with anyone.')
            ->line('If you did not request this code, you can ignore this email.');
    }
}
