<?php

namespace App\Mail;

use App\Models\DemandeDemo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DemandeDemoRecueMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly DemandeDemo $demande)
    {
    }

    public function build(): self
    {
        return $this->subject('Nouvelle demande de démo')
            ->view('emails.demande-demo-recue', [
                'demande' => $this->demande,
            ]);
    }
}
