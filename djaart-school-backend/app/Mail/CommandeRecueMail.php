<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommandeRecueMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Commande $commande)
    {
    }

    public function build(): self
    {
        return $this->subject('Nouvelle commande DJAART SCHOOL')
            ->view('emails.commande-recue', [
                'commande' => $this->commande,
            ]);
    }
}
