<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompteCreeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $user)
    {
    }

    public function build(): self
    {
        return $this->subject("Votre compte DJAART SCHOOL a été créé")
            ->view('emails.compte-cree', [
                'user' => $this->user,
                'roleLabel' => $this->user->getRoleNames()->first() ?? 'utilisateur',
                'etablissementNom' => $this->user->etablissement?->nom,
                'loginUrl' => rtrim(config('app.frontend_url'), '/').'/login',
            ]);
    }
}
