<?php

namespace App\Services;

use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaiementService
{
    public function __construct(
        private readonly RecuService $recuService,
        private readonly EcheancierService $echeancierService,
    ) {
    }

    /**
     * Encaisse un montant libre contre le solde de la pension totale de
     * l'inscription (pas une tranche precise) : peut couvrir plusieurs
     * tranches d'un coup ou en soldrer une seule partiellement, tant que le
     * montant ne depasse pas ce qui reste du a la pension.
     */
    public function encaisser(array $data, User $caissier): Paiement
    {
        return DB::transaction(function () use ($data, $caissier) {
            $inscription = Inscription::where('id', $data['inscription_id'])->lockForUpdate()->first();

            $soldeTotalPension = $this->echeancierService->soldeTotalPension($inscription);

            if ($soldeTotalPension <= 0) {
                throw ValidationException::withMessages([
                    'montant' => 'La pension est déjà intégralement payée pour cet apprenant.',
                ]);
            }

            $montant = (float) $data['montant'];

            if ($montant > $soldeTotalPension) {
                throw ValidationException::withMessages([
                    'montant' => "Le montant saisi ({$montant}) dépasse le solde restant de la pension ({$soldeTotalPension}).",
                ]);
            }

            $paiement = Paiement::create([
                'etablissement_id' => $inscription->etablissement_id,
                'inscription_id' => $inscription->id,
                'tranche_id' => null,
                'montant' => $montant,
                'mode_paiement' => $data['mode_paiement'],
                'caissier_id' => $caissier->id,
                'date_paiement' => now()->toDateString(),
            ]);

            $this->recuService->genererPour($paiement);

            $this->validerInscriptionSiFraisInscriptionCouverts($inscription);

            return $paiement->load(['inscription.apprenant', 'inscription.classe', 'recu']);
        });
    }

    /**
     * Les frais d'inscription sont un montant paramétré sur la grille de frais
     * (inclus dans la pension, pas un supplément), qui valide l'inscription dès
     * que le cumul des paiements de l'apprenant l'atteint — sans attendre que la
     * tranche en cours soit intégralement soldée.
     */
    private function validerInscriptionSiFraisInscriptionCouverts(Inscription $inscription): void
    {
        if ($inscription->statut !== 'en_cours') {
            return;
        }

        $fraisInscription = (float) $inscription->fraisScolarite->frais_inscription;
        $totalPaye = $this->echeancierService->totalPaye($inscription);

        if ($totalPaye >= $fraisInscription) {
            $inscription->update(['statut' => 'validee']);
        }
    }
}
