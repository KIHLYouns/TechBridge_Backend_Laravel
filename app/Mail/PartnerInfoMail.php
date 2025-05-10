<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class PartnerInfoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $partner;

    public function __construct(User $partner)
    {
        $this->partner = $partner;
    }

    public function build()
    {
        return $this->subject('Reservation Partner Information')
                    ->markdown('emails.partner_info');
    }
}

