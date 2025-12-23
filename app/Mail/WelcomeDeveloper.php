<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeDeveloper extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;

    public function __construct($name, $email,$password)
    {
        $this->name  = $name;
        $this->email = $email;
        $this->password = $password;
    }


    public function build()
    {
        return $this->subject('Welcome to MySyncTax Developer Network')
            ->view('emails.welcome')
            ->with([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password
            ]);

    }
}
