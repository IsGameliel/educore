<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentAccountCreated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $student,
        public string $plainPassword
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Educore Student Account Details',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student_account_created',
            with: [
                'student' => $this->student,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => route('login'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
