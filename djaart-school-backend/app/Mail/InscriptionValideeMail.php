<?php

namespace App\Mail;

use App\Models\Inscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InscriptionValideeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Inscription $inscription)
    {
    }

    public function build(): self
    {
        return $this->subject('Inscription validée')
            ->view('emails.inscription-validee', [
                'apprenant' => $this->inscription->apprenant,
                'classeLibelle' => $this->inscription->classe->libelle,
                'etablissementNom' => $this->inscription->classe->etablissement->nom,
            ]);
    }
}
