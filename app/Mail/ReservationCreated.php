<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationCreated extends Mailable
{
    use Queueable, SerializesModels;

    public array $payload;
    public bool $forRestaurant;

    /**
     * @param array $payload  reservation + restaurant info
     * @param bool  $forRestaurant if true, subject/body are tuned for staff
     */
    public function __construct(array $payload, bool $forRestaurant = false)
    {
        $this->payload = $payload;
        $this->forRestaurant = $forRestaurant;
    }

    public function build()
    {
        $subject = $this->forRestaurant
            ? 'Nasi Lemak Burung Hantu Reservation: [#'.$this->payload['reservation_id'].'] '.$this->payload['restaurant']['name']
            : 'Nasi Lemak Burung Hantu Reservation: [#'.$this->payload['reservation_id'].'] '.$this->payload['restaurant']['name'];

        return $this->subject($subject)
            ->view('emails.reservations.created')
            ->with($this->payload);
    }
}
