<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deux garde-fous globaux (hors super_admin, jamais concerne) :
 * - mot de passe provisoire non change (cf. Commande::valider) -> 423, tant
 *   que PUT /api/moi/mot-de-passe n'a pas ete appele ;
 * - abonnement de l'etablissement actif expire (duree d'acces choisie a la
 *   validation de la commande) -> 403.
 * Routes exemptees (doivent rester joignables pour se debloquer soi-meme) :
 * /logout, /me, /moi/mot-de-passe — cf. routes/api.php.
 */
class EnsureAccountUsable
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->hasRole('super_admin')) {
            return $next($request);
        }

        if ($user->must_change_password) {
            abort(423, 'Vous devez changer votre mot de passe avant de continuer.');
        }

        $etablissement = $user->etablissement;
        if ($etablissement && $etablissement->abonnement_expire_le && $etablissement->abonnement_expire_le->isPast()) {
            abort(403, "L'abonnement de votre établissement a expiré. Contactez votre administrateur DJAART SCHOOL.");
        }

        return $next($request);
    }
}
