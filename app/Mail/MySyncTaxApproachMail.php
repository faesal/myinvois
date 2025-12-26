<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MySyncTaxApproachMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;

    public function __construct($name = 'Sir / Madam')
    {
        $this->name = $name;
    }

    public function build()
    {
        return $this->subject('MySyncTax Partnership Opportunity – LHDN MyInvois Integration')
            ->view('emails.mysynctax_approach');
    }
}
