<?php

namespace App\Services;

use App\Models\Inscription;
use App\Models\Paiement;
use Illuminate\Support\Collection;

/**
 * Un paiement n'est plus rattache a une tranche precise (cf. correctif encaissement
 * flexible) : le solde de reference est celui de la pension totale de l'inscription.
 * Le detail par tranche (payee/partielle/en attente/en retard) est une donnee DERIVEE,
 * calculee en repartissant le total paye sur les tranches dans l'ordre de leur numero
 * ("cascade" - une tranche est soldee avant que le surplus ne s'applique a la suivante).
 * Centralise ici pour eviter que ApprenantController::echeancier et
 * RapportService::listerImpayes ne dupliquent (et ne divergent) ce calcul.
 */
class EcheancierService
{
    public function totalPaye(Inscription $inscription): float
    {
        return (float) Paiement::where('inscription_id', $inscription->id)->sum('montant');
    }

    public function soldeTotalPension(Inscription $inscription): float
    {
        $montantTotal = (float) $inscription->fraisScolarite->montant_total;

        return round($montantTotal - $this->totalPaye($inscription), 2);
    }

    /**
     * @return Collection<int, array{id: int, numero: int, montant: float, date_echeance: string, montant_paye: float, solde: float, statut: string}>
     */
    public function calculerTranches(Inscription $inscription): Collection
    {
        $tranches = $inscription->fraisScolarite->tranches->sortBy('numero')->values();
        $restantAAllouer = $this->totalPaye($inscription);

        return $tranches->map(function ($tranche) use (&$restantAAllouer) {
            $montantTranche = (float) $tranche->montant;
            $montantPaye = round(min($restantAAllouer, $montantTranche), 2);
            $restantAAllouer = round(max($restantAAllouer - $montantPaye, 0), 2);
            $solde = round($montantTranche - $montantPaye, 2);

            $statut = match (true) {
                $solde <= 0 => 'payee',
                $montantPaye > 0 => 'partielle',
                $tranche->date_echeance->isPast() => 'en_retard',
                default => 'en_attente',
            };

            return [
                'id' => $tranche->id,
                'numero' => $tranche->numero,
                'montant' => $montantTranche,
                'date_echeance' => $tranche->date_echeance->toDateString(),
                'montant_paye' => $montantPaye,
                'solde' => $solde,
                'statut' => $statut,
            ];
        });
    }
}
