<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envoie un e-mail sans jamais faire echouer l'action metier qui l'a
 * declenchee (paiement, inscription, creation de compte, demande de
 * demo...) — un incident de livraison (destinataire rejete par le
 * serveur distant, panne SMTP temporaire...) est journalise, pas
 * remonte comme une erreur 500 qui masquerait a tort un enregistrement
 * pourtant reussi en base. L'appelant peut lire la valeur de retour
 * pour adapter son message si l'e-mail portait une information qui
 * n'existe nulle part ailleurs (ex. mot de passe genere).
 */
class Mailer
{
    public static function envoyer(callable $envoi): bool
    {
        try {
            $envoi();

            return true;
        } catch (Throwable $e) {
            Log::error('Envoi e-mail echoue: '.$e->getMessage(), ['exception' => $e]);

            return false;
        }
    }
}
