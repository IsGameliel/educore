<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;

class TuitionPaymentReminder extends Mailable
{
    public function __construct(public string $invoiceNumber, public string $studentName, public array $notice, public string $paymentUrl) {}
    public function build()
    {
        return $this->subject($this->notice['label'].' — '.$this->invoiceNumber)->view('emails.tuition-reminder');
    }
}
