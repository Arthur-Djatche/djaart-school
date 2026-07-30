<?php

namespace App\Mail;

use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Notification (sans mot de passe, le compte existe deja) quand un
 * admin_etablissement deja existant se voit rattacher un 2e etablissement
 * via la validation d'une commande.
 */
class NouvelEtablissementAjouteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $user, public readonly Etablissement $etablissement)
    {
    }

    public function build(): self
    {
        return $this->subject('Un nouvel établissement a été ajouté à votre compte')
            ->view('emails.nouvel-etablissement-ajoute', [
                'user' => $this->user,
                'etablissement' => $this->etablissement,
                'loginUrl' => rtrim(config('app.frontend_url'), '/').'/login',
            ]);
    }
}
