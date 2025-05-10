<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class ClientInfoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $client;

    public function __construct(User $client)
    {
        $this->client = $client;
    }

    public function build()
    {
        return $this->subject('Reservation Client Information')
                    ->markdown('emails.client_info');
    }
}
