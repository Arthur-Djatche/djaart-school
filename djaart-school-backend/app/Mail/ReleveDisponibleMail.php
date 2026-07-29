<?php

namespace App\Mail;

use App\Models\ReleveDeNotes;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReleveDisponibleMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ReleveDeNotes $releve)
    {
    }

    public function build(): self
    {
        return $this->subject('Relevé de notes disponible')
            ->view('emails.releve-disponible', [
                'apprenant' => $this->releve->inscription->apprenant,
                'releve' => $this->releve,
            ]);
    }
}
