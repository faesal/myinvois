<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailyConsolidateSummary extends Mailable
{
    use Queueable, SerializesModels;

    public $summary;

    public function __construct($summary,$customSubject)
    {
        $this->summary = $summary;
        $this->customSubject = $customSubject;
    }

    public function build()
    {
        return $this->subject($this->customSubject)
                    ->markdown('consolidate.summary');
    }
}
