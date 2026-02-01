<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClassScheduledNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $schedule;
    public $courseTitle;

    /**
     * Create a new message instance.
     *
     * @param  mixed  $schedule
     * @param  string  $courseTitle
     */
    public function __construct($schedule, $courseTitle)
    {
        $this->schedule = $schedule;
        $this->courseTitle = $courseTitle;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Class Scheduled Notification',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.class_scheduled', // Update with the actual view name for the email
            with: [
                'schedule' => $this->schedule,
                'courseTitle' => $this->courseTitle,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
