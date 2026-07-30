<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommandeValideeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $user, public readonly string $motDePasse)
    {
    }

    public function build(): self
    {
        return $this->subject('Votre espace DJAART SCHOOL est prêt')
            ->view('emails.commande-validee', [
                'user' => $this->user,
                'motDePasse' => $this->motDePasse,
                'etablissementNom' => $this->user->etablissement?->nom,
                'loginUrl' => rtrim(config('app.frontend_url'), '/').'/login',
            ]);
    }
}
