<?php

namespace App\Mail;

use App\Models\Recu;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class PaiementRecuMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Recu $recu)
    {
    }

    public function build(): self
    {
        $paiement = $this->recu->paiement;

        return $this->subject('Reçu de paiement')
            ->view('emails.paiement-recu', [
                'apprenant' => $paiement->inscription->apprenant,
                'paiement' => $paiement,
                'numeroRecu' => $this->recu->numero_recu,
            ])
            ->attach(
                Attachment::fromStorageDisk('local', $this->recu->fichier_pdf)
                    ->as("recu-{$this->recu->numero_recu}.pdf")
                    ->withMime('application/pdf'),
            );
    }
}
