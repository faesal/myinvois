<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MySyncTaxCredentialMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customer;
    public $password;
    public $sendProduction;
    public $sendSandbox;

    public function __construct($customer, $password, $sendProduction = true, $sendSandbox = true)
    {
        $this->customer        = $customer;
        $this->password        = $password;
        $this->sendProduction  = $sendProduction;
        $this->sendSandbox     = $sendSandbox;
    }

    public function build()
    {
        return $this->subject('MySyncTax Account Access Details')
            ->view('emails.mysynctax.credentials');
    }
}
