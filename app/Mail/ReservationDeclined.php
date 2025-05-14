<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationDeclined extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;
    public $partnerName;

    public function __construct($reservation, $partnerName)
    {
        $this->reservation = $reservation;
        $this->partnerName = $partnerName;
    }

    public function build()
    {
        return $this->subject('Votre réservation a été refusée')
                    ->view('emails.reservation_declined');
    }
}