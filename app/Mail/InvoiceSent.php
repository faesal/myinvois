<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceSent extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;
    public $customer;
    public $items;
    public $supplier;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($invoice, $customer, $items, $supplier)
    {
        $this->invoice = $invoice;
        $this->customer = $customer;
        $this->items = $items;
        $this->supplier = $supplier;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('e-Invoice Submission - ' . $this->invoice->invoice_no)
                    ->view('emails.invoice_notification'); // Ensure this view exists
    }
}