<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SubscriberExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subscribers; // Changed from single '$subscriber' to plural '$subscribers'
    public $reportDate;

    public function __construct($subscribers)
    {
        $this->subscribers = $subscribers;
        $this->reportDate = now()->format('M d, Y');
    }

    public function build()
    {
        return $this->subject('Expired Subscribers Report (' . $this->reportDate . ')')
                    ->view('emails.subscriber_expired');
    }
}