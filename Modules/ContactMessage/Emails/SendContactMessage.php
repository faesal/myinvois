<?php

namespace Modules\ContactMessage\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $mail_subject;
    public $mail_message;
    public $from_mail;
    public $from_name;

    public function __construct($mail_message, $mail_subject, $from_mail, $from_name)
    {
        $this->mail_subject = $mail_subject;
        $this->mail_message = $mail_message;
        $this->from_mail = $from_mail;
        $this->from_name = $from_name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->mail_subject)->view('contactmessage::contact_message_email', ['mail_message' => $this->mail_message]);
    }
}
