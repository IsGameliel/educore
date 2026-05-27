<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserAccountCredentials extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $plainPassword,
        public string $action = 'created'
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->action === 'updated'
                ? 'Your Educore Account Details Were Updated'
                : 'Your Educore Account Login Details',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user_account_credentials',
            with: [
                'user' => $this->user->loadMissing('department.faculty'),
                'plainPassword' => $this->plainPassword,
                'action' => $this->action,
                'loginUrl' => route('login'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
