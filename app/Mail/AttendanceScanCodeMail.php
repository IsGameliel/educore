<?php

namespace App\Mail;

use App\Models\AttendanceSession;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AttendanceScanCodeMail extends Mailable
{

    public function __construct(
        public AttendanceSession $attendanceSession,
        public User $student
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Attendance Scan Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.attendance_scan_code',
            with: [
                'attendanceSession' => $this->attendanceSession,
                'student' => $this->student,
                'scanUrl' => route('attendance.scan.register', $this->attendanceSession->scan_token),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
