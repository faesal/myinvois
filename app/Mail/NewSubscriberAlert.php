<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // Added this
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewSubscriberAlert extends Mailable implements ShouldQueue // Added implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $subscriber;
    public $developer;

    public function __construct($subscriber, $developer)
    {
        // We use $subscriber and $developer as objects/arrays
        $this->subscriber = $subscriber;
        $this->developer = $developer;
    }

    public function build()
    {
        return $this->subject('Action Required: New LHDN Account Added [' . $this->subscriber->registration_name . ']')
                    ->view('emails.new_subscriber_alert');
    }
}