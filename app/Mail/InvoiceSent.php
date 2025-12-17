<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceSent extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice, $customer,$items,$supplier;

    public function __construct($invoice, $customer,$items,$supplier)
    {
        $this->invoice = $invoice;
        $this->customer = $customer;
        $this->items = $items;
        $this->supplier = $supplier;
    }

    public function build()
    {
        return $this->subject('MySyncTax eInvoice - #' . $this->invoice->invoice_no)
            ->markdown('emails.sent');
    }
}

?>