<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Contrairement a CommandeValideeMail (acces payant, duree choisie en mois),
 * l'acces de demo est fixe a 48h (cf. DemandeDemoController::valider) — le
 * mail le rappelle explicitement avec la date/heure d'expiration exacte.
 */
class DemandeDemoValideeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $user, public readonly string $motDePasse)
    {
    }

    public function build(): self
    {
        return $this->subject('Votre accès de démonstration DJAART SCHOOL est prêt')
            ->view('emails.demande-demo-validee', [
                'user' => $this->user,
                'motDePasse' => $this->motDePasse,
                'etablissementNom' => $this->user->etablissement?->nom,
                'expireLe' => $this->user->etablissement?->abonnement_expire_le,
                'loginUrl' => rtrim(config('app.frontend_url'), '/').'/login',
            ]);
    }
}
